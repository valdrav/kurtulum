<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;

class RecordDeletionPolicy
{
    public function __construct(protected OrderFinanceService $finance) {}

    public function orderDeleteBlockReason(Order $order): ?string
    {
        if ($order->trashed()) {
            return __('orders.already_deleted');
        }

        if (! in_array($order->status, ['draft', 'cancelled'], true)) {
            return __('orders.delete_use_cancel_instead');
        }

        if ($order->collections()->exists() || $order->payments()->exists()) {
            return __('orders.delete_has_finance_movements');
        }

        if ($order->finance_posted_at || $this->finance->orderHasCariPostings($order)) {
            return $order->status === 'cancelled'
                ? __('orders.delete_finance_still_open')
                : __('orders.delete_use_cancel_instead');
        }

        return null;
    }

    public function shipmentDeleteBlockReason(Shipment $shipment): ?string
    {
        if ($shipment->trashed()) {
            return __('logistics.already_deleted');
        }

        if (! in_array($shipment->status, ['draft', 'cancelled'], true)) {
            return __('logistics.delete_use_cancel_instead');
        }

        if ($shipment->costs()->exists()) {
            return __('logistics.delete_has_costs');
        }

        return null;
    }
}
