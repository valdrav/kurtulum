<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderLifecycleService
{
    public function __construct(protected OrderFinanceService $finance) {}

    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);
            $this->finance->resyncOrderLedger($order->fresh());

            return $order->fresh();
        });
    }

    public function duplicate(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order->load('items');

            $copy = $order->replicate(['uuid', 'order_number', 'finance_posted_at', 'amount_collected', 'amount_paid']);
            $copy->order_number = $this->nextOrderNumber();
            $copy->status = 'draft';
            $copy->finance_posted_at = null;
            $copy->amount_collected = 0;
            $copy->amount_paid = 0;
            $copy->save();

            foreach ($order->items as $item) {
                $newItem = $item->replicate(['uuid']);
                $newItem->order_id = $copy->id;
                $newItem->save();
            }

            return $copy->fresh(['items', 'customer', 'supplier']);
        });
    }

    public function restore(Order $order): Order
    {
        $order->restore();

        return $order->fresh();
    }

    protected function nextOrderNumber(): string
    {
        $year = now()->format('Y');
        $last = Order::withTrashed()
            ->where('order_number', 'like', "ORD-{$year}-%")
            ->count() + 1;

        return sprintf('ORD-%s-%04d', $year, $last);
    }
}
