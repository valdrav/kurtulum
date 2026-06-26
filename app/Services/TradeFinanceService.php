<?php

namespace App\Services;

use App\Models\IncomeExpense;
use App\Models\Order;
use App\Models\ShipmentCost;

class TradeFinanceService
{
    public function __construct(
        protected ExchangeRateService $rates,
    ) {}

    public function tradeCurrency(): string
    {
        return trade_currency();
    }

    public function toCurrency(float $amount, string $fromCurrency, string $toCurrency, ?float $exchangeRate = null): float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return round($amount, 2);
        }

        $converted = $this->rates->convert($amount, $fromCurrency, $toCurrency);

        if ($converted !== null) {
            return round($converted, 2);
        }

        if ($exchangeRate !== null && $exchangeRate > 0) {
            $default = registry()->defaultCurrency()?->code ?? 'TRY';

            if ($fromCurrency !== $default && $toCurrency === $default) {
                return round($amount * $exchangeRate, 2);
            }

            if ($fromCurrency === $default && $toCurrency !== $default) {
                return round($amount / $exchangeRate, 2);
            }
        }

        return round($amount, 2);
    }

    public function toTradeCurrency(float $amount, string $fromCurrency, ?float $exchangeRate = null): float
    {
        return $this->toCurrency($amount, $fromCurrency, $this->tradeCurrency(), $exchangeRate);
    }

    public function dualReceivables(): array
    {
        return $this->dualTotals(fn (Order $order) => max(0, (float) $order->sale_total - (float) $order->amount_collected));
    }

    public function dualPayables(): array
    {
        return $this->dualTotals(fn (Order $order) => max(0, (float) $order->purchase_total - (float) $order->amount_paid));
    }

    public function dualMargin(): array
    {
        return $this->dualTotals(fn (Order $order) => (float) ($order->margin_total ?? 0));
    }

    public function dualMonthlyMargin(?int $month = null, ?int $year = null): array
    {
        $month ??= (int) now()->month;
        $year ??= (int) now()->year;

        return $this->dualTotals(
            fn (Order $order) => (float) ($order->margin_total ?? 0),
            Order::query()
                ->whereMonth('order_date', $month)
                ->whereYear('order_date', $year)
                ->whereNotIn('status', ['cancelled'])
        );
    }

    public function dualTotals(callable $amountResolver, $query = null): array
    {
        $query ??= Order::query()->whereNotIn('status', ['cancelled', 'draft']);

        $native = ['USD' => 0.0, 'TRY' => 0.0];

        $query->get()->each(function (Order $order) use ($amountResolver, &$native) {
            $amount = (float) $amountResolver($order);

            if ($amount <= 0) {
                return;
            }

            $currency = strtoupper($order->currency ?? $this->tradeCurrency());
            $native[$currency] = ($native[$currency] ?? 0) + $amount;
        });

        return [
            'USD' => round($native['USD'] + $this->toCurrency($native['TRY'], 'TRY', 'USD'), 2),
            'TRY' => round($native['TRY'] + $this->toCurrency($native['USD'], 'USD', 'TRY'), 2),
            'native' => $native,
        ];
    }

    public function totalReceivables(): float
    {
        return $this->sumOpenOrders(fn (Order $order) => max(0, (float) $order->sale_total - (float) $order->amount_collected));
    }

    public function totalPayables(): float
    {
        return $this->sumOpenOrders(fn (Order $order) => max(0, (float) $order->purchase_total - (float) $order->amount_paid));
    }

    public function totalMargin(): float
    {
        return $this->sumOpenOrders(fn (Order $order) => (float) ($order->margin_total ?? 0));
    }

    public function monthlyMargin(?int $month = null, ?int $year = null): float
    {
        $month ??= (int) now()->month;
        $year ??= (int) now()->year;

        return Order::query()
            ->whereMonth('order_date', $month)
            ->whereYear('order_date', $year)
            ->whereNotIn('status', ['cancelled'])
            ->get()
            ->sum(fn (Order $order) => $this->toTradeCurrency(
                (float) ($order->margin_total ?? 0),
                $order->currency ?? $this->tradeCurrency()
            ));
    }

    protected function sumOpenOrders(callable $amountResolver): float
    {
        return Order::query()
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->get()
            ->sum(function (Order $order) use ($amountResolver) {
                $amount = (float) $amountResolver($order);

                if ($amount <= 0) {
                    return 0.0;
                }

                return $this->toTradeCurrency($amount, $order->currency ?? $this->tradeCurrency());
            });
    }

    public function amountInOrderCurrency(float $amount, string $fromCurrency, string $orderCurrency, ?float $exchangeRate = null): float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $orderCurrency = strtoupper($orderCurrency);

        if ($fromCurrency === $orderCurrency) {
            return round($amount, 2);
        }

        $converted = $this->rates->convert($amount, $fromCurrency, $orderCurrency);

        if ($converted !== null) {
            return round($converted, 2);
        }

        return $this->toCurrency($amount, $fromCurrency, $orderCurrency, $exchangeRate);
    }

    /** Siparişe bağlı lojistik masrafları ve gider kayıtları. */
    public function orderRelatedExpenses(Order $order): array
    {
        $order->loadMissing(['shipments.costs']);
        $currency = strtoupper($order->currency ?? $this->tradeCurrency());
        $items = [];
        $total = 0.0;

        foreach ($order->shipments as $shipment) {
            foreach ($shipment->costs as $cost) {
                $amount = $this->expenseLineAmount($cost, $currency);
                if ($amount <= 0) {
                    continue;
                }
                $total += $amount;
                $items[] = [
                    'source' => 'shipment_cost',
                    'label' => $cost->displayTitle(),
                    'meta' => $shipment->shipment_number,
                    'amount' => $amount,
                    'currency' => $currency,
                ];
            }
        }

        IncomeExpense::query()
            ->where('type', 'expense')
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->each(function (IncomeExpense $entry) use (&$total, &$items, $currency) {
                $amount = $this->amountInOrderCurrency(
                    (float) $entry->amount,
                    $entry->currency ?? 'TRY',
                    $currency,
                    $entry->exchange_rate ? (float) $entry->exchange_rate : null
                );
                if ($amount <= 0) {
                    return;
                }
                $total += $amount;
                $items[] = [
                    'source' => 'income_expense',
                    'label' => $entry->displayTitle(),
                    'meta' => $entry->transaction_date?->format('d.m.Y'),
                    'amount' => $amount,
                    'currency' => $currency,
                ];
            });

        return [
            'total' => round($total, 2),
            'currency' => $currency,
            'items' => $items,
        ];
    }

    protected function expenseLineAmount(ShipmentCost $cost, string $targetCurrency): float
    {
        $from = strtoupper($cost->currency ?? 'TRY');

        if ($from === $targetCurrency) {
            return round((float) $cost->amount, 2);
        }

        if ($targetCurrency === 'TRY' && $cost->amount_try) {
            return round((float) $cost->amount_try, 2);
        }

        return $this->amountInOrderCurrency(
            (float) $cost->amount,
            $from,
            $targetCurrency,
            $cost->exchange_rate ? (float) $cost->exchange_rate : null
        );
    }
}
