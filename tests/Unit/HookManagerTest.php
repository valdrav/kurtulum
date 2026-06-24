<?php

namespace Tests\Unit;

use App\Core\HookManager;
use App\Core\PaymentMethodService;
use App\Models\PaymentMethod;
use Tests\FeatureTestCase;

class HookManagerTest extends FeatureTestCase
{
    public function test_hook_filter_modifies_value(): void
    {
        $hooks = new HookManager();
        $hooks->register('test.filter', fn ($value) => $value . '-modified');

        $result = $hooks->filter('test.filter', 'original');

        $this->assertSame('original-modified', $result);
    }

    public function test_payment_fee_calculation_percent(): void
    {
        $method = PaymentMethod::where('code', 'credit_card')->first();
        $service = app(PaymentMethodService::class);

        $fee = $service->calculateFee($method, 1000);

        $this->assertSame(25.0, $fee);
    }

    public function test_registry_formats_money(): void
    {
        $formatted = registry()->formatMoney(1234.56, 'TRY');

        $this->assertStringContainsString('1.234,56', $formatted);
    }
}
