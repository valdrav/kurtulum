<?php

namespace Tests\Feature;

use App\Models\IncomeExpense;
use Tests\FeatureTestCase;

class ReportTest extends FeatureTestCase
{
    public function test_finance_report_page_works(): void
    {
        $this->actingAsAdmin();

        IncomeExpense::create([
            'type' => 'income',
            'category' => 'sales_export',
            'item_name' => 'Demo gelir',
            'amount' => 1000,
            'currency' => 'TRY',
            'exchange_rate' => 1,
            'amount_base' => 1000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Demo gelir',
            'account_id' => company_treasury()->defaultAccount()->id,
            'user_id' => auth()->id(),
        ]);

        $this->get(route('reports.finance', ['period' => 'month']))
            ->assertOk()
            ->assertSee('1.000,00')
            ->assertSee(__('finance.income_period'));
    }

    public function test_sales_report_renders_twelve_months(): void
    {
        $this->actingAsAdmin();

        $this->get(route('reports.sales', ['year' => now()->year]))
            ->assertOk()
            ->assertSee(__('reports.month'));
    }

    public function test_logistics_report_renders(): void
    {
        $this->actingAsAdmin();

        $this->get(route('reports.logistics'))
            ->assertOk()
            ->assertSee(__('reports.total_shipments'));
    }

    public function test_finance_index_redirects_to_treasury(): void
    {
        $this->actingAsAdmin();

        $this->get(route('finance.index'))
            ->assertRedirect(route('finance.treasury'));
    }
}
