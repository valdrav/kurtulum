<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Shipment;
use App\Models\ShipmentCost;
use App\Models\Supplier;
use App\Services\OrderFinanceService;
use Tests\FeatureTestCase;

class OrderShipmentDeleteTest extends FeatureTestCase
{
    protected function createConfirmedOrder(float $sale = 10000, float $purchase = 7000): Order
    {
        $customer = Customer::create([
            'company_name' => 'Silme Test Müşteri',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'TRY',
        ]);

        $supplier = Supplier::create([
            'company_name' => 'Silme Test Tedarikçi',
            'status' => 'active',
            'currency' => 'TRY',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-DEL-0001',
            'customer_id' => $customer->id,
            'supplier_id' => $supplier->id,
            'status' => 'confirmed',
            'order_date' => now(),
            'currency' => 'TRY',
            'sale_total' => $sale,
            'purchase_total' => $purchase,
            'margin_total' => $sale - $purchase,
            'total_amount' => $sale,
        ]);

        app(OrderFinanceService::class)->postOrderLedger($order->fresh());

        return $order->fresh();
    }

    public function test_order_delete_reverses_finance_and_related_records(): void
    {
        $this->actingAsAdmin();
        company_treasury()->ensureDefaults();

        $order = $this->createConfirmedOrder();
        $customerAccount = Account::where('customer_id', $order->customer_id)->first();
        $treasury = company_treasury()->defaultAccount();
        $method = PaymentMethod::where('code', 'cash')->first();

        $this->post(route('finance.collections.store'), [
            'order_id' => $order->id,
            'account_id' => $customerAccount->id,
            'treasury_account_id' => $treasury->id,
            'payment_method_id' => $method->id,
            'amount' => 5000,
            'currency' => 'TRY',
            'collection_date' => now()->toDateString(),
        ])->assertRedirect();

        IncomeExpense::create([
            'type' => 'expense',
            'category' => 'shipping',
            'item_name' => 'Sipariş gideri',
            'amount' => 200,
            'currency' => 'TRY',
            'exchange_rate' => 1,
            'amount_base' => 200,
            'account_id' => $treasury->id,
            'transaction_date' => now(),
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'user_id' => auth()->id(),
        ]);

        $shipment = Shipment::create([
            'shipment_number' => 'SHP-DEL-0001',
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'transport_mode' => 'road',
            'status' => 'draft',
            'currency' => 'TRY',
        ]);

        ShipmentCost::create([
            'shipment_id' => $shipment->id,
            'type' => 'freight',
            'description' => 'Navlun',
            'amount' => 150,
            'currency' => 'TRY',
        ]);

        $response = $this->delete(route('orders.destroy', $order));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('warning');

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
        $this->assertSoftDeleted('shipments', ['id' => $shipment->id]);
        $this->assertEquals(0, Collection::where('order_id', $order->id)->count());
        $this->assertEquals(0, Payment::where('order_id', $order->id)->count());
        $this->assertEquals(0, IncomeExpense::where('reference_type', Order::class)->where('reference_id', $order->id)->count());
        $this->assertEquals(0, ShipmentCost::where('shipment_id', $shipment->id)->count());

        $customerAccount->refresh();
        $this->assertEquals(0, (float) $customerAccount->current_balance);
    }

    public function test_shipment_delete_removes_costs(): void
    {
        $this->actingAsAdmin();

        $shipment = Shipment::create([
            'shipment_number' => 'SHP-DEL-0002',
            'transport_mode' => 'sea',
            'status' => 'draft',
            'currency' => 'USD',
        ]);

        $cost = ShipmentCost::create([
            'shipment_id' => $shipment->id,
            'type' => 'freight',
            'description' => 'Navlun',
            'amount' => 500,
            'currency' => 'USD',
        ]);

        $response = $this->delete(route('shipments.destroy', $shipment));

        $response->assertRedirect(route('shipments.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('warning');

        $this->assertSoftDeleted('shipments', ['id' => $shipment->id]);
        $this->assertSoftDeleted('shipment_costs', ['id' => $cost->id]);
    }
}
