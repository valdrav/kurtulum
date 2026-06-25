<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\PaymentMethod;
use Tests\FeatureTestCase;

class FinanceTest extends FeatureTestCase
{
    public function test_admin_can_view_payments_page(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('finance.payments'));

        $response->assertOk();
        $response->assertSee(__('app.payments'));
    }

    public function test_admin_can_create_payment_with_dynamic_method(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::create([
            'company_name' => 'Finance Test Co',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'TRY',
        ]);

        $account = Account::create([
            'code' => 'ACC-001',
            'name' => 'Test Cari',
            'type' => 'customer',
            'customer_id' => $customer->id,
            'currency' => 'TRY',
            'opening_balance' => 1000,
            'current_balance' => 1000,
            'is_active' => true,
        ]);

        $method = PaymentMethod::where('code', 'cash')->first();

        $response = $this->post(route('finance.payments.store'), [
            'account_id' => $account->id,
            'payment_method_id' => $method->id,
            'amount' => 100,
            'currency' => 'TRY',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'account_id' => $account->id,
            'payment_method_id' => $method->id,
            'amount' => 100,
        ]);
    }

    public function test_payment_method_fields_api_returns_json(): void
    {
        $this->actingAsAdmin();
        $method = PaymentMethod::where('code', 'bank_transfer')->first();

        $response = $this->get(route('settings.payment-methods.fields', $method));

        $response->assertOk();
        $response->assertJsonStructure(['method', 'fields', 'currencies', 'features']);
    }

    public function test_income_expense_deducts_from_treasury(): void
    {
        $this->actingAsAdmin();

        $treasury = company_treasury()->defaultAccount();
        $before = (float) $treasury->current_balance;

        $response = $this->post(route('finance.income-expenses.store'), [
            'type' => 'expense',
            'item_name' => 'Ekmek',
            'amount' => 50,
            'currency' => 'TRY',
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('income_expenses', [
            'item_name' => 'Ekmek',
            'category' => 'food_bread',
            'account_id' => $treasury->id,
            'type' => 'expense',
        ]);

        $treasury->refresh();
        $this->assertEquals($before - 50, (float) $treasury->current_balance);
    }

    public function test_profit_loss_report_with_periods(): void
    {
        $this->actingAsAdmin();

        IncomeExpense::create([
            'type' => 'expense',
            'category' => 'Yemek',
            'item_name' => 'Kahvaltı',
            'amount' => 100,
            'currency' => 'TRY',
            'exchange_rate' => 1,
            'amount_base' => 100,
            'transaction_date' => now()->toDateString(),
            'description' => 'Kahvaltı',
            'account_id' => company_treasury()->defaultAccount()->id,
            'user_id' => auth()->id(),
        ]);

        $this->get(route('finance.profit-loss', ['period' => 'day', 'date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Kahvaltı')
            ->assertSee(__('finance.period_day'));

        $this->get(route('finance.profit-loss', ['period' => 'month']))
            ->assertOk()
            ->assertSee(__('finance.by_category'));
    }

    public function test_income_expense_delete_reverses_treasury_balance(): void
    {
        $this->actingAsAdmin();

        $treasury = company_treasury()->defaultAccount();
        $before = (float) $treasury->current_balance;

        $this->post(route('finance.income-expenses.store'), [
            'type' => 'expense',
            'item_name' => 'Test silinecek',
            'amount' => 75,
            'currency' => 'TRY',
            'transaction_date' => now()->toDateString(),
        ])->assertRedirect();

        $entry = IncomeExpense::where('item_name', 'Test silinecek')->firstOrFail();
        $treasury->refresh();
        $this->assertEquals($before - 75, (float) $treasury->current_balance);

        $this->delete(route('finance.income-expenses.destroy', $entry))->assertRedirect();

        $treasury->refresh();
        $this->assertEquals($before, (float) $treasury->current_balance);
        $this->assertSoftDeleted('income_expenses', ['id' => $entry->id]);
    }

    public function test_usd_income_converts_to_try_treasury(): void
    {
        $this->actingAsAdmin();

        $treasury = company_treasury()->defaultAccount();
        $before = (float) $treasury->current_balance;

        $this->post(route('finance.income-expenses.store'), [
            'type' => 'income',
            'item_name' => 'USD tahsilat',
            'amount' => 50000,
            'currency' => 'USD',
            'exchange_rate' => 34.5,
            'transaction_date' => now()->toDateString(),
        ])->assertRedirect();

        $treasury->refresh();
        $this->assertEquals($before + 1725000, (float) $treasury->current_balance);
    }
}
