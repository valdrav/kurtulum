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
        return app(CustomerProfileService::class)
            ->ordersQueryForCustomer($this->customer())
            ->latest('order_date');
    }

    public function shipmentsQuery(): Builder
    {
        $customer = $this->customer();
        $customerId = $this->customerId();
        $orderIds = $this->ordersQuery()->select('orders.id');

        return Shipment::query()
            ->where(function (Builder $query) use ($customerId, $orderIds, $customer) {
                $query->where('customer_id', $customerId)
                    ->orWhereIn('order_id', $orderIds)
                    ->orWhereHas('order', function (Builder $order) use ($customer) {
                        app(CustomerProfileService::class)->applyCustomerOrderScope($order, $customer);
                    });
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

    public function findCost(string $key): \App\Models\ShipmentCost
    {
        $cost = \App\Models\ShipmentCost::query()
            ->with('shipment')
            ->where((new \App\Models\ShipmentCost)->getRouteKeyName(), $key)
            ->firstOrFail();

        $this->findShipment($cost->shipment->uuid);

        return $cost;
    }
}
