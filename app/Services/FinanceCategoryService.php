<?php

namespace App\Services;

class FinanceCategoryService
{
    public function incomeGroups(): array
    {
        return __('finance.income_category_groups');
    }

    public function expenseGroups(): array
    {
        return __('finance.expense_category_groups');
    }

    public function groupsForType(string $type): array
    {
        return $type === 'income' ? $this->incomeGroups() : $this->expenseGroups();
    }

    public function flat(string $type): array
    {
        $flat = [];
        foreach ($this->groupsForType($type) as $group) {
            foreach ($group['items'] ?? [] as $key => $label) {
                $flat[$key] = $label;
            }
        }

        return $flat;
    }

    public function label(?string $key): string
    {
        if (! $key) {
            return '—';
        }

        foreach (['income', 'expense'] as $type) {
            if (isset($this->flat($type)[$key])) {
                return $this->flat($type)[$key];
            }
        }

        return __('finance.legacy_categories')[$key] ?? $key;
    }

    /** @return list<string> */
    public function itemSuggestions(): array
    {
        return __('finance.item_suggestions');
    }

    public function paymentMethods(): array
    {
        return __('finance.expense_payment_methods');
    }

    public function units(): array
    {
        return __('finance.units');
    }

    /** Açıklama metninden kategori anahtarı türetir (raporlama için). */
    public function infer(string $type, string $itemName, ?string $vendor = null, ?string $notes = null): string
    {
        $text = $this->normalizeText(implode(' ', array_filter([$itemName, $vendor, $notes])));

        if ($text === '') {
            return $type === 'income' ? 'other_income' : 'misc';
        }

        $aliases = $this->keywordAliases($type);
        uksort($aliases, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($aliases as $keyword => $categoryKey) {
            if (str_contains($text, $this->normalizeText($keyword))) {
                return $categoryKey;
            }
        }

        foreach ($this->flat($type) as $key => $label) {
            $normalizedLabel = $this->normalizeText($label);
            if ($normalizedLabel !== '' && str_contains($text, $normalizedLabel)) {
                return $key;
            }

            foreach ($this->significantWords($label) as $word) {
                if (mb_strlen($word) >= 4 && str_contains($text, $word)) {
                    return $key;
                }
            }
        }

        return $type === 'income' ? 'other_income' : 'misc';
    }

    protected function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = str_replace(['ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ş', 'Ş', 'ö', 'Ö', 'ç', 'Ç'], ['i', 'i', 'g', 'g', 'u', 'u', 's', 's', 'o', 'o', 'c', 'c'], $text);

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }

    /** @return list<string> */
    protected function significantWords(string $label): array
    {
        $parts = preg_split('/[\s\/\(\),\-–]+/u', mb_strtolower($label, 'UTF-8')) ?: [];

        return array_values(array_filter($parts, fn ($w) => mb_strlen($w) >= 3));
    }

    /** @return array<string, string> */
    protected function keywordAliases(string $type): array
    {
        if ($type === 'income') {
            return [
                'ihracat tahsilat' => 'sales_export',
                'ihracat satis' => 'sales_export',
                'ihracat' => 'sales_export',
                'yurtici satis' => 'sales_domestic',
                'yurtici' => 'sales_domestic',
                'navlun gelir' => 'sales_logistics',
                'lojistik gelir' => 'sales_logistics',
                'hizmet gelir' => 'sales_service',
                'komisyon gelir' => 'sales_commission',
                'faiz gelir' => 'interest_income',
                'kur farki gelir' => 'fx_gain',
                'sigorta tazmin' => 'insurance_claim',
                'kira gelir' => 'rental_income',
                'tesvik' => 'grant_subsidy',
                'hibe' => 'grant_subsidy',
            ];
        }

        return [
            'ekmek' => 'food_bread',
            'su fatur' => 'utility_water',
            'dogalgaz' => 'utility_gas',
            'dogal gaz' => 'utility_gas',
            'elektrik' => 'utility_electric',
            'internet fatur' => 'utility_internet',
            'telefon fatur' => 'utility_phone',
            'gsm' => 'utility_phone',
            'ofis kir' => 'rent_office',
            'depo kir' => 'rent_warehouse',
            'aidat' => 'rent_dues',
            'personel yemek' => 'food_staff',
            'yemek kart' => 'food_meal',
            'yemek' => 'food_meal',
            'cay' => 'food_tea_coffee',
            'kahve' => 'food_tea_coffee',
            'ikram' => 'food_snacks',
            'kirtasiye' => 'office_supplies',
            'toner' => 'office_print',
            'temizlik' => 'office_cleaning',
            'navlun' => 'logistics_freight',
            'freight' => 'logistics_freight',
            'demurrage' => 'logistics_demurrage',
            'detention' => 'logistics_demurrage',
            'gumruk vergi' => 'customs_duty',
            'gumruk' => 'customs_fee',
            'tercume' => 'translation',
            'noter' => 'notary',
            'maas' => 'salary',
            'prim' => 'salary_bonus',
            'sgk' => 'salary_sgk',
            'stopaj' => 'salary_tax',
            'mazot' => 'vehicle_fuel',
            'benzin' => 'vehicle_fuel',
            'yakit' => 'vehicle_fuel',
            'otopark' => 'vehicle_parking',
            'otoyol' => 'vehicle_toll',
            'ucak' => 'travel_flight',
            'otel' => 'travel_hotel',
            'taksi' => 'travel_taxi',
            'vize' => 'travel_visa',
            'fuar' => 'travel_fair',
            'banka masraf' => 'bank_fee',
            'komisyon' => 'bank_commission',
            'pos' => 'bank_commission',
            'kdv' => 'tax_vat',
            'damga vergi' => 'tax_stamp',
            'sigorta' => 'insurance_general',
            'kargo' => 'office_post',
            'kurye' => 'office_post',
            'muhasebe' => 'accounting',
            'avukat' => 'legal',
            'danismanlik' => 'consulting',
            'reklam' => 'marketing',
        ];
    }
}
