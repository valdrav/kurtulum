<?php

namespace Tests\Unit;

use App\Services\FinanceCategoryService;
use Tests\TestCase;

class FinanceCategoryServiceTest extends TestCase
{
    public function test_infers_expense_category_from_description(): void
    {
        $service = new FinanceCategoryService();

        $this->assertSame('food_bread', $service->infer('expense', 'Ekmek'));
        $this->assertSame('utility_electric', $service->infer('expense', 'Elektrik faturası'));
        $this->assertSame('logistics_freight', $service->infer('expense', 'Navlun ödemesi'));
        $this->assertSame('salary', $service->infer('expense', 'Maaş ödemeleri'));
    }

    public function test_infers_income_category_from_description(): void
    {
        $service = new FinanceCategoryService();

        $this->assertSame('sales_export', $service->infer('income', 'İhracat tahsilatı'));
        $this->assertSame('sales_domestic', $service->infer('income', 'Yurtiçi satış geliri'));
    }
}
