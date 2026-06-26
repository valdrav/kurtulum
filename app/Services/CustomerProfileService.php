<?php

namespace App\Services;

use App\Models\AccountTransaction;
use App\Models\Collection as CollectionModel;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CustomerProfileService
{
    public function summary(Customer $customer): array
    {
        $ordersQuery = $this->ordersQuery($customer);

        $saleTotal = (float) (clone $ordersQuery)->sum('sale_total');
        $amountCollected = (float) (clone $ordersQuery)->sum('amount_collected');

        return [
            'order_count' => (clone $ordersQuery)->count(),
            'sale_total' => $saleTotal,
            'amount_collected' => $amountCollected,
            'remaining_receivable' => max(0, $saleTotal - $amountCollected),
            'margin_total' => (float) (clone $ordersQuery)->sum('margin_total'),
            'collection_count' => $this->collectionsQuery($customer)->count(),
            'shipment_count' => $customer->shipments()->whereNotIn('status', ['cancelled'])->count(),
        ];
    }

    public function orders(Customer $customer, int $limit = 50): Collection
    {
        return $this->ordersQuery($customer)
            ->with(['supplier', 'items.product'])
            ->latest('order_date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function aggregatedProducts(Customer $customer): Collection
    {
        return OrderItem::query()
            ->select('product_id', 'description')
            ->selectRaw('SUM(quantity) as total_qty')
            ->selectRaw('MAX(unit) as unit')
            ->selectRaw('SUM(total) as total_sale')
            ->selectRaw('COUNT(DISTINCT order_id) as order_count')
            ->whereHas('order', fn ($q) => $this->applyCustomerOrderScope($q, $customer))
            ->groupBy('product_id', 'description')
            ->orderByDesc('total_sale')
            ->get();
    }

    public function productLines(Customer $customer, int $limit = 100): Collection
    {
        return OrderItem::query()
            ->whereHas('order', fn ($q) => $this->applyCustomerOrderScope($q, $customer))
            ->with(['order.supplier', 'product'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function collections(Customer $customer, int $limit = 40): Collection
    {
        return $this->collectionsQuery($customer)
            ->with(['order', 'account'])
            ->latest('collection_date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    protected function ordersQuery(Customer $customer)
    {
        return Order::query()
            ->where(function (Builder $q) use ($customer) {
                $this->applyCustomerOrderScope($q, $customer);
            })
            ->whereNotIn('status', ['cancelled']);
    }

    protected function applyCustomerOrderScope(Builder $query, Customer $customer): void
    {
        $query->where(function (Builder $q) use ($customer) {
            $q->where('customer_id', $customer->id)
                ->orWhereHas('collections', fn (Builder $c) => $c->where('customer_id', $customer->id))
                ->orWhereIn('id', AccountTransaction::query()
                    ->where('reference_type', Order::class)
                    ->whereHas('account', fn (Builder $a) => $a->where('customer_id', $customer->id))
                    ->select('reference_id'));
        });
    }

    protected function collectionsQuery(Customer $customer)
    {
        return CollectionModel::query()->where(function ($q) use ($customer) {
            $q->where('customer_id', $customer->id)
                ->orWhereHas('account', fn ($a) => $a->where('customer_id', $customer->id));
        });
    }
}
