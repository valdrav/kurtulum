<?php

namespace Database\Seeders;

use App\Models\LookupType;
use App\Models\LookupValue;
use App\Models\PaymentMethod;
use App\Models\SystemCurrency;
use App\Models\SystemLanguage;
use App\Models\SystemModule;
use Illuminate\Database\Seeder;

class ExtensibilitySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLanguages();
        $this->seedCurrencies();
        $this->seedPaymentMethods();
        $this->seedLookups();
        $this->seedCoreModules();
    }

    protected function seedLanguages(): void
    {
        $languages = [
            ['code' => 'tr', 'name' => 'Turkish', 'native_name' => 'Türkçe', 'direction' => 'ltr', 'flag' => 'tr', 'is_default' => true, 'sort_order' => 1],
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'flag' => 'gb', 'sort_order' => 2],
            ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'flag' => 'sa', 'sort_order' => 3],
            ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'direction' => 'ltr', 'flag' => 'de', 'sort_order' => 4],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr', 'flag' => 'fr', 'sort_order' => 5],
        ];

        foreach ($languages as $lang) {
            SystemLanguage::updateOrCreate(['code' => $lang['code']], array_merge($lang, ['is_active' => true]));
        }
    }

    protected function seedCurrencies(): void
    {
        $currencies = [
            ['code' => 'TRY', 'name' => 'Türk Lirası', 'symbol' => '₺', 'exchange_rate' => 1, 'is_default' => true, 'sort_order' => 1],
            ['code' => 'USD', 'name' => 'ABD Doları', 'symbol' => '$', 'exchange_rate' => 1, 'sort_order' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 1, 'sort_order' => 3],
            ['code' => 'SAR', 'name' => 'Suudi Riyali', 'symbol' => '﷼', 'exchange_rate' => 1, 'sort_order' => 4],
            ['code' => 'GBP', 'name' => 'İngiliz Sterlini', 'symbol' => '£', 'exchange_rate' => 1, 'sort_order' => 10, 'is_active' => false],
            ['code' => 'AED', 'name' => 'BAE Dirhemi', 'symbol' => 'د.إ', 'exchange_rate' => 1, 'sort_order' => 11, 'is_active' => false],
            ['code' => 'CNY', 'name' => 'Çin Yuanı', 'symbol' => '¥', 'exchange_rate' => 1, 'sort_order' => 12, 'is_active' => false],
            ['code' => 'RUB', 'name' => 'Rus Rublesi', 'symbol' => '₽', 'exchange_rate' => 1, 'sort_order' => 13, 'is_active' => false],
        ];

        foreach ($currencies as $cur) {
            $active = $cur['is_active'] ?? true;
            unset($cur['is_active']);
            SystemCurrency::updateOrCreate(['code' => $cur['code']], array_merge($cur, ['is_active' => $active]));
        }
    }

    protected function seedPaymentMethods(): void
    {
        $methods = [
            [
                'code' => 'cash',
                'name' => 'Nakit',
                'type' => 'both',
                'icon' => 'ti-cash',
                'features' => ['instant', 'no_fee'],
                'fee_type' => 'none',
                'sort_order' => 1,
            ],
            [
                'code' => 'bank_transfer',
                'name' => 'Banka Havalesi / EFT',
                'type' => 'both',
                'icon' => 'ti-building-bank',
                'requires_reference' => true,
                'requires_bank_account' => true,
                'config_schema' => [
                    'fields' => [
                        ['name' => 'bank_name', 'label' => 'Banka Adı', 'type' => 'text', 'required' => true],
                        ['name' => 'iban', 'label' => 'IBAN', 'type' => 'text', 'required' => true],
                        ['name' => 'swift', 'label' => 'SWIFT/BIC', 'type' => 'text', 'required' => false],
                    ],
                ],
                'features' => ['reference_required', 'multi_currency'],
                'sort_order' => 2,
            ],
            [
                'code' => 'check',
                'name' => 'Çek',
                'type' => 'both',
                'icon' => 'ti-file-invoice',
                'requires_reference' => true,
                'config_schema' => [
                    'fields' => [
                        ['name' => 'check_number', 'label' => 'Çek No', 'type' => 'text', 'required' => true],
                        ['name' => 'check_bank', 'label' => 'Banka', 'type' => 'text', 'required' => true],
                        ['name' => 'due_date', 'label' => 'Vade Tarihi', 'type' => 'date', 'required' => true],
                    ],
                ],
                'features' => ['reference_required', 'post_dated'],
                'sort_order' => 3,
            ],
            [
                'code' => 'credit_card',
                'name' => 'Kredi Kartı',
                'type' => 'collection',
                'icon' => 'ti-credit-card',
                'is_online' => true,
                'fee_type' => 'percent',
                'fee_amount' => 2.5,
                'config_schema' => [
                    'fields' => [
                        ['name' => 'card_last_four', 'label' => 'Kart Son 4 Hane', 'type' => 'text', 'required' => true],
                        ['name' => 'installment', 'label' => 'Taksit', 'type' => 'select', 'options' => ['1', '3', '6', '9', '12'], 'required' => false],
                    ],
                ],
                'features' => ['online', 'installment'],
                'supported_currencies' => ['TRY'],
                'sort_order' => 4,
            ],
            [
                'code' => 'letter_of_credit',
                'name' => 'Akreditif (L/C)',
                'type' => 'both',
                'icon' => 'ti-certificate',
                'requires_reference' => true,
                'config_schema' => [
                    'fields' => [
                        ['name' => 'lc_number', 'label' => 'L/C Numarası', 'type' => 'text', 'required' => true],
                        ['name' => 'issuing_bank', 'label' => 'Amir Banka', 'type' => 'text', 'required' => true],
                        ['name' => 'expiry_date', 'label' => 'Vade', 'type' => 'date', 'required' => true],
                    ],
                ],
                'features' => ['export', 'document_required'],
                'sort_order' => 5,
            ],
            [
                'code' => 'paypal',
                'name' => 'PayPal',
                'type' => 'both',
                'icon' => 'ti-brand-paypal',
                'is_online' => true,
                'fee_type' => 'percent',
                'fee_amount' => 3.4,
                'config_schema' => [
                    'fields' => [
                        ['name' => 'transaction_id', 'label' => 'PayPal İşlem ID', 'type' => 'text', 'required' => true],
                        ['name' => 'payer_email', 'label' => 'Ödeyen E-posta', 'type' => 'email', 'required' => false],
                    ],
                ],
                'features' => ['online', 'multi_currency'],
                'sort_order' => 6,
            ],
            [
                'code' => 'crypto',
                'name' => 'Kripto Para (USDT)',
                'type' => 'both',
                'icon' => 'ti-currency-bitcoin',
                'is_online' => true,
                'config_schema' => [
                    'fields' => [
                        ['name' => 'wallet_address', 'label' => 'Cüzdan Adresi', 'type' => 'text', 'required' => true],
                        ['name' => 'tx_hash', 'label' => 'TX Hash', 'type' => 'text', 'required' => true],
                        ['name' => 'network', 'label' => 'Ağ', 'type' => 'select', 'options' => ['TRC20', 'ERC20', 'BEP20'], 'required' => true],
                    ],
                ],
                'features' => ['online', 'multi_currency'],
                'supported_currencies' => ['USD', 'EUR'],
                'sort_order' => 7,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(['code' => $method['code']], array_merge([
                'is_active' => true,
                'fee_type' => 'none',
                'fee_amount' => 0,
            ], $method));
        }
    }

    protected function seedLookups(): void
    {
        $types = [
            'incoterms' => [
                'name' => 'Incoterms',
                'is_system' => true,
                'values' => ['EXW', 'FCA', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP', 'FAS', 'FOB', 'CFR', 'CIF'],
            ],
            'order_statuses' => [
                'name' => 'Sipariş Durumları',
                'is_system' => true,
                'values' => ['draft', 'confirmed', 'production', 'ready', 'shipped', 'delivered', 'cancelled'],
            ],
            'shipment_statuses' => [
                'name' => 'Sevkiyat Durumları',
                'is_system' => true,
                'values' => [
                    'draft', 'planned', 'booked', 'loading', 'in_transit',
                    'port_waiting', 'awaiting_transit', 'at_port', 'discharging',
                    'customs', 'delivered', 'completed', 'cancelled',
                ],
            ],
            'transport_modes' => [
                'name' => 'Taşıma Modları',
                'is_system' => true,
                'values' => ['road', 'sea', 'air', 'rail', 'multimodal'],
            ],
        ];

        foreach ($types as $slug => $data) {
            $type = LookupType::updateOrCreate(
                ['slug' => $slug],
                ['name' => $data['name'], 'is_system' => $data['is_system'], 'is_active' => true]
            );

            foreach ($data['values'] as $i => $code) {
                LookupValue::updateOrCreate(
                    ['lookup_type_id' => $type->id, 'code' => $code],
                    ['label' => strtoupper(str_replace('_', ' ', $code)), 'sort_order' => $i + 1, 'is_active' => true]
                );
            }
        }
    }

    protected function seedCoreModules(): void
    {
        $coreModules = [
            ['slug' => 'crm', 'name' => 'CRM', 'is_core' => true, 'is_enabled' => true],
            ['slug' => 'logistics', 'name' => 'Lojistik', 'is_core' => true, 'is_enabled' => true],
            ['slug' => 'finance', 'name' => 'Finans', 'is_core' => true, 'is_enabled' => true],
        ];

        foreach ($coreModules as $mod) {
            SystemModule::updateOrCreate(['slug' => $mod['slug']], array_merge($mod, [
                'version' => config('ticari.version', '1.0.0'),
                'installed_at' => now(),
            ]));
        }
    }
}
