<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ExternalApiConnection;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;

class ExternalApiContextService
{
    public function __construct(
        public ExternalApiConnection $connection,
    ) {}

    public function customer(): Customer
    {
        return $this->connection->customer;
    }

    public function customerId(): int
    {
        return (int) $this->connection->customer_id;
    }

    public function allows(string $feature): bool
    {
        return $this->connection->allows($feature);
    }

    public function ordersQuery(): Builder
    {
        $customerId = $this->customerId();

        return Order::query()
            ->where('customer_id', $customerId)
            ->latest('order_date');
    }

    public function shipmentsQuery(): Builder
    {
        $customerId = $this->customerId();

        return Shipment::query()
            ->where(function (Builder $query) use ($customerId) {
                $query->where('customer_id', $customerId)
                    ->orWhereHas('order', fn (Builder $order) => $order->where('customer_id', $customerId));
            })
            ->latest('created_at');
    }

    public function findOrder(string $key): Order
    {
        return $this->ordersQuery()
            ->where((new Order)->getRouteKeyName(), $key)
            ->firstOrFail();
    }

    public function findShipment(string $key): Shipment
    {
        return $this->shipmentsQuery()
            ->where((new Shipment)->getRouteKeyName(), $key)
            ->firstOrFail();
    }
}
