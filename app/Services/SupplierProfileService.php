<?php

namespace App\Services;

use App\Models\AccountTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ShipmentCost;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SupplierProfileService
{
    public function summary(Supplier $supplier): array
    {
        $ordersQuery = $this->ordersQuery($supplier);

        $purchaseTotal = (float) (clone $ordersQuery)->sum('purchase_total');
        $amountPaid = (float) (clone $ordersQuery)->sum('amount_paid');

        return [
            'order_count' => (clone $ordersQuery)->count(),
            'purchase_total' => $purchaseTotal,
            'amount_paid' => $amountPaid,
            'remaining_payable' => max(0, $purchaseTotal - $amountPaid),
            'margin_total' => (float) (clone $ordersQuery)->sum('margin_total'),
            'payment_count' => $this->paymentsQuery($supplier)->count(),
            'shipment_cost_total' => (float) ShipmentCost::query()
                ->where('supplier_id', $supplier->id)
                ->sum('amount'),
        ];
    }

    public function orders(Supplier $supplier, int $limit = 50): Collection
    {
        return $this->ordersQuery($supplier)
            ->with(['customer', 'items.product'])
            ->latest('order_date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function aggregatedProducts(Supplier $supplier): Collection
    {
        return OrderItem::query()
            ->select('product_id', 'description')
            ->selectRaw('SUM(quantity) as total_qty')
            ->selectRaw('MAX(unit) as unit')
            ->selectRaw('SUM(purchase_total) as total_purchase')
            ->selectRaw('COUNT(DISTINCT order_id) as order_count')
            ->whereHas('order', fn ($q) => $this->applySupplierOrderScope($q, $supplier))
            ->groupBy('product_id', 'description')
            ->orderByDesc('total_purchase')
            ->get();
    }

    public function productLines(Supplier $supplier, int $limit = 100): Collection
    {
        return OrderItem::query()
            ->whereHas('order', fn ($q) => $this->applySupplierOrderScope($q, $supplier))
            ->with(['order.customer', 'product'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function unlinkedOrderCount(Supplier $supplier): int
    {
        return $this->ordersQuery($supplier, includeUnlinked: true)->count();
    }

    /** Siparişlerde supplier_id eksik olsa bile ödeme/cari kaydından bağlı siparişleri bulur. */
    public function backfillOrderLinks(Supplier $supplier): int
    {
        $updated = 0;

        $this->ordersQuery($supplier, includeUnlinked: true)
            ->whereNull('supplier_id')
            ->where('purchase_total', '>', 0)
            ->each(function (Order $order) use ($supplier, &$updated) {
                $order->update(['supplier_id' => $supplier->id]);
                $updated++;
            });

        return $updated;
    }

    public function backfillAllMissingSuppliers(): int
    {
        $updated = 0;

        Order::query()
            ->whereNull('supplier_id')
            ->where('purchase_total', '>', 0)
            ->whereNotIn('status', ['cancelled'])
            ->each(function (Order $order) use (&$updated) {
                $supplierId = $this->inferSupplierId($order);
                if ($supplierId) {
                    $order->update(['supplier_id' => $supplierId]);
                    $updated++;
                }
            });

        return $updated;
    }

    public function payments(Supplier $supplier, int $limit = 40): Collection
    {
        return $this->paymentsQuery($supplier)
            ->with(['order', 'account'])
            ->latest('payment_date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function shipmentCosts(Supplier $supplier, int $limit = 40): Collection
    {
        return ShipmentCost::query()
            ->with(['shipment'])
            ->where('supplier_id', $supplier->id)
            ->latest('expense_date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    protected function ordersQuery(Supplier $supplier, bool $includeUnlinked = false)
    {
        return Order::query()
            ->where(function (Builder $q) use ($supplier, $includeUnlinked) {
                $this->applySupplierOrderScope($q, $supplier, $includeUnlinked);
            })
            ->whereNotIn('status', ['cancelled']);
    }

    protected function applySupplierOrderScope(Builder $query, Supplier $supplier, bool $includeUnlinkedOnly = false): void
    {
        $query->where(function (Builder $q) use ($supplier, $includeUnlinkedOnly) {
            if (! $includeUnlinkedOnly) {
                $q->where('supplier_id', $supplier->id);
            }

            $q->orWhereHas('payments', fn (Builder $p) => $p->where('supplier_id', $supplier->id))
                ->orWhereIn('id', AccountTransaction::query()
                    ->where('reference_type', Order::class)
                    ->whereHas('account', fn (Builder $a) => $a->where('supplier_id', $supplier->id))
                    ->select('reference_id'));
        });

        if ($includeUnlinkedOnly) {
            $query->whereNull('supplier_id');
        }
    }

    protected function inferSupplierId(Order $order): ?int
    {
        $fromPayment = Payment::query()
            ->where('order_id', $order->id)
            ->whereNotNull('supplier_id')
            ->value('supplier_id');

        if ($fromPayment) {
            return (int) $fromPayment;
        }

        $accountSupplierId = AccountTransaction::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->whereHas('account', fn (Builder $a) => $a->whereNotNull('supplier_id'))
            ->with('account:id,supplier_id')
            ->first()
            ?->account
            ?->supplier_id;

        return $accountSupplierId ? (int) $accountSupplierId : null;
    }

    protected function paymentsQuery(Supplier $supplier)
    {
        return Payment::query()->where(function ($q) use ($supplier) {
            $q->where('supplier_id', $supplier->id)
                ->orWhereHas('account', fn ($a) => $a->where('supplier_id', $supplier->id));
        });
    }
}
