<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPortalAccess;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PortalContextService
{
    public function __construct(
        public User $user,
        public CustomerPortalAccess $access,
    ) {}

    public function customer(): Customer
    {
        return $this->access->customer;
    }

    public function customerId(): int
    {
        return (int) $this->access->customer_id;
    }

    public function allows(string $feature): bool
    {
        return $this->access->allows($feature);
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

    public function assertOrderAccess(Order $order): void
    {
        abort_unless((int) $order->customer_id === $this->customerId(), 404);
    }

    public function assertShipmentAccess(Shipment $shipment): void
    {
        abort_unless((int) $shipment->customer_id === $this->customerId(), 404);
    }
}
