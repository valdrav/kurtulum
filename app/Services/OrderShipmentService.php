<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentMilestone;
use Illuminate\Support\Facades\DB;

class OrderShipmentService
{
    /** @var list<string> */
    public const SHIPMENT_ELIGIBLE_STATUSES = [
        'confirmed',
        'production',
        'ready',
        'shipped',
        'delivered',
    ];

    public function ensureShipmentForOrder(Order $order): ?Shipment
    {
        $order->loadMissing(['items', 'shipments']);

        if ($order->shipments->isNotEmpty()) {
            return $order->shipments->first();
        }

        if (! in_array($order->status, self::SHIPMENT_ELIGIBLE_STATUSES, true)) {
            return null;
        }

        return DB::transaction(function () use ($order) {
            $shipment = Shipment::create([
                'shipment_number' => $this->generateNumber('SHP'),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'transport_mode' => $this->inferTransportMode($order),
                'status' => 'draft',
                'incoterm' => $order->incoterm,
                'currency' => $order->currency ?? 'USD',
                'eta' => $order->delivery_date,
                'cargo_description' => $this->cargoDescription($order),
                'notes' => trim(($order->notes ? $order->notes . "\n" : '') . __('orders.shipment_auto_note', ['order' => $order->order_number])),
                'assigned_user_id' => $order->assigned_user_id,
                'created_by' => auth()->id(),
            ]);

            $this->createDefaultMilestones($shipment);

            return $shipment;
        });
    }

    public function syncOrdersWithoutShipments(): int
    {
        $count = 0;

        Order::query()
            ->whereIn('status', self::SHIPMENT_ELIGIBLE_STATUSES)
            ->whereDoesntHave('shipments')
            ->with('items')
            ->orderBy('id')
            ->chunkById(50, function ($orders) use (&$count) {
                foreach ($orders as $order) {
                    if ($this->ensureShipmentForOrder($order)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function cargoDescriptionForOrder(Order $order): ?string
    {
        return $this->cargoDescription($order);
    }

    protected function cargoDescription(Order $order): ?string
    {
        $text = $order->items
            ->pluck('description')
            ->map(fn ($d) => trim((string) $d))
            ->filter()
            ->implode(', ');

        return $text !== '' ? $text : null;
    }

    protected function inferTransportMode(Order $order): string
    {
        $incoterm = strtoupper((string) $order->incoterm);

        if (in_array($incoterm, ['FOB', 'CFR', 'CIF', 'FAS'], true)) {
            return 'sea';
        }

        if (in_array($incoterm, ['FCA', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP', 'EXW'], true)) {
            return 'road';
        }

        return 'road';
    }

    protected function createDefaultMilestones(Shipment $shipment): void
    {
        $defaults = [
            __('logistics.milestone_booking'),
            __('logistics.milestone_loading'),
            __('logistics.milestone_departure'),
            __('logistics.milestone_arrival'),
            __('logistics.milestone_customs'),
            __('logistics.milestone_delivery'),
        ];

        foreach ($defaults as $title) {
            ShipmentMilestone::create([
                'shipment_id' => $shipment->id,
                'name' => $title,
                'status' => 'pending',
            ]);
        }
    }

    protected function generateNumber(string $prefix): string
    {
        return $prefix . '-' . date('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }
}
