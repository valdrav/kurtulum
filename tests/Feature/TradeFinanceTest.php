<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentCost;
use App\Models\SystemCurrency;
use App\Services\OrderFinanceService;
use App\Services\TradeFinanceService;
use Tests\FeatureTestCase;

class TradeFinanceTest extends FeatureTestCase
{
    protected function seedUsdRate(): void
    {
        SystemCurrency::updateOrCreate(['code' => 'TRY'], [
            'name' => 'Türk Lirası',
            'symbol' => '₺',
            'exchange_rate' => 1,
            'tcmb_rate' => 1,
            'market_rate' => 1,
            'is_active' => true,
        ]);

        SystemCurrency::updateOrCreate(['code' => 'USD'], [
            'name' => 'ABD Doları',
            'symbol' => '$',
            'exchange_rate' => 35,
            'tcmb_rate' => 35,
            'market_rate' => 35,
            'is_active' => true,
        ]);
    }

    public function test_dashboard_receivables_use_trade_currency_from_orders(): void
    {
        $this->actingAsAdmin();
        $this->seedUsdRate();

        $customer = Customer::create([
            'company_name' => 'USD Müşteri',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        Order::create([
            'order_number' => 'ORD-USD-RCV',
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'order_date' => now(),
            'currency' => 'USD',
            'sale_total' => 10000,
            'purchase_total' => 7000,
            'margin_total' => 3000,
            'total_amount' => 10000,
            'amount_collected' => 2000,
        ]);

        $receivables = app(TradeFinanceService::class)->totalReceivables();

        $this->assertEquals(8000.0, $receivables);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('8.000 USD');
    }

    public function test_order_expenses_reduce_net_margin(): void
    {
        $this->seedUsdRate();

        $customer = Customer::create([
            'company_name' => 'Test',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-EXP-001',
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'order_date' => now(),
            'currency' => 'USD',
            'sale_total' => 5000,
            'purchase_total' => 3000,
            'margin_total' => 2000,
            'total_amount' => 5000,
        ]);

        $shipment = Shipment::create([
            'shipment_number' => 'SHP-001',
            'order_id' => $order->id,
            'transport_mode' => 'sea',
            'status' => 'booked',
        ]);

        ShipmentCost::create([
            'shipment_id' => $shipment->id,
            'type' => 'freight',
            'item_name' => 'Navlun',
            'amount' => 500,
            'currency' => 'USD',
            'expense_date' => now(),
            'status' => 'paid',
        ]);

        IncomeExpense::create([
            'type' => 'expense',
            'category' => 'logistics',
            'item_name' => 'Gümrük',
            'amount' => 3500,
            'currency' => 'TRY',
            'exchange_rate' => 35,
            'amount_base' => 3500,
            'transaction_date' => now(),
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);

        $summary = app(OrderFinanceService::class)->financeSummary($order->fresh());

        $this->assertEquals(600.0, $summary['order_expenses']);
        $this->assertEquals(1400.0, $summary['net_margin']);
    }
}
