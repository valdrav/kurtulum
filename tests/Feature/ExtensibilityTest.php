<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\SystemCurrency;
use App\Models\SystemLanguage;
use Tests\FeatureTestCase;

class ExtensibilityTest extends FeatureTestCase
{
    public function test_languages_are_seeded(): void
    {
        $this->assertGreaterThanOrEqual(3, SystemLanguage::count());
        $this->assertNotNull(registry()->defaultLanguage());
    }

    public function test_currencies_are_seeded(): void
    {
        $this->assertGreaterThanOrEqual(5, SystemCurrency::count());
        $this->assertNotNull(registry()->defaultCurrency());
    }

    public function test_payment_methods_are_seeded(): void
    {
        $this->assertGreaterThanOrEqual(5, PaymentMethod::count());
        $this->assertNotNull(payment_methods()->findByCode('bank_transfer'));
    }

    public function test_admin_can_view_language_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('settings.languages.index'));

        $response->assertOk();
        $response->assertSee('Türkçe');
    }

    public function test_admin_can_add_currency(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('settings.currencies.store'), [
            'code' => 'JPY',
            'name' => 'Japanese Yen',
            'symbol' => '¥',
            'decimal_places' => 0,
            'exchange_rate' => 0.22,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('system_currencies', ['code' => 'JPY']);
    }

    public function test_lookup_incoterms_available(): void
    {
        $incoterms = registry()->lookup('incoterms');

        $this->assertTrue($incoterms->contains('code', 'FOB'));
        $this->assertTrue($incoterms->contains('code', 'CIF'));
    }

    public function test_all_core_languages_have_translation_files(): void
    {
        $required = ['app.php', 'auth.php', 'messages.php', 'logistics.php', 'extensions.php', 'settings.php'];

        foreach (['tr', 'en', 'ar', 'de', 'fr'] as $code) {
            $this->assertDirectoryExists(lang_path($code), "Missing lang directory: {$code}");
            foreach ($required as $file) {
                $this->assertFileExists(lang_path("{$code}/{$file}"), "Missing {$code}/{$file}");
            }
        }
    }

    public function test_user_can_switch_locale_to_german(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('locale.switch', 'de'));

        $response->assertRedirect();
        $this->assertSame('de', session('locale'));
        $this->assertSame('Dashboard', __('app.dashboard'));
    }
}
