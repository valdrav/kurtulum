<?php

namespace App\Services;

use App\Models\ShipmentCost;

class ShipmentCostTreasuryService
{
    public function __construct(
        protected AccountLedgerService $ledger,
        protected ExchangeRateService $rates,
    ) {}

    public function syncPaidStatus(ShipmentCost $cost, ?string $previousStatus = null): void
    {
        $wasPaid = $previousStatus === 'paid';
        $isPaid = $cost->status === 'paid';

        if ($wasPaid && ! $isPaid) {
            $this->reverseTreasury($cost);
        } elseif (! $wasPaid && $isPaid) {
            $this->applyTreasury($cost);
        } elseif ($wasPaid && $isPaid) {
            $this->reverseTreasury($cost);
            $this->applyTreasury($cost->fresh());
        }
    }

    public function reverseOnDelete(ShipmentCost $cost): void
    {
        if ($cost->status === 'paid' && $cost->treasury_posted_at) {
            $this->reverseTreasury($cost);
        }
    }

    protected function applyTreasury(ShipmentCost $cost): void
    {
        if ($cost->treasury_posted_at || $cost->status !== 'paid') {
            return;
        }

        $treasury = company_treasury()->defaultAccount();
        $amount = $this->rates->amountForAccount(
            (float) $cost->amount,
            $cost->currency,
            $treasury,
            (float) ($cost->exchange_rate ?: null)
        );

        $this->ledger->adjustBalance(
            $treasury,
            -$amount,
            $cost,
            'Sevkiyat masrafı: ' . $cost->displayTitle(),
            ($cost->paid_at ?? $cost->expense_date)?->toDateString() ?? now()->toDateString(),
            null,
            (float) ($cost->exchange_rate ?: 1)
        );

        $cost->update(['treasury_posted_at' => now()]);
    }

    protected function reverseTreasury(ShipmentCost $cost): void
    {
        if (! $cost->treasury_posted_at) {
            return;
        }

        $treasury = company_treasury()->defaultAccount();
        $amount = $this->rates->amountForAccount(
            (float) $cost->amount,
            $cost->currency,
            $treasury,
            (float) ($cost->exchange_rate ?: null)
        );

        $this->ledger->adjustBalance(
            $treasury,
            $amount,
            $cost,
            'İptal sevkiyat masrafı: ' . $cost->displayTitle(),
            now()->toDateString(),
            null,
            (float) ($cost->exchange_rate ?: 1)
        );

        $cost->update(['treasury_posted_at' => null]);
    }
}
