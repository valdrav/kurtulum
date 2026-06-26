<?php

if (!function_exists('locale_flag')) {
    function locale_flag(?string $flagCode): string
    {
        if (! $flagCode || strlen($flagCode) !== 2) {
            return '🌐';
        }

        $flagCode = strtoupper($flagCode);

        return mb_chr(127462 + ord($flagCode[0]) - 65) . mb_chr(127462 + ord($flagCode[1]) - 65);
    }
}

if (!function_exists('locale_flag_for')) {
    function locale_flag_for(?object $language): string
    {
        if (! $language) {
            return '🌐';
        }

        $flag = $language->flag ?? config('ticari.locales.' . $language->code . '.flag');

        return locale_flag(is_string($flag) ? $flag : null);
    }
}

if (!function_exists('hook')) {
    function hook(): \App\Core\HookManager
    {
        return app(\App\Core\HookManager::class);
    }
}

if (!function_exists('registry')) {
    function registry(): \App\Core\Registry
    {
        return app(\App\Core\Registry::class);
    }
}

if (!function_exists('modules')) {
    function modules(): \App\Core\ModuleManager
    {
        return app(\App\Core\ModuleManager::class);
    }
}

if (!function_exists('payment_methods')) {
    function payment_methods(): \App\Core\PaymentMethodService
    {
        return app(\App\Core\PaymentMethodService::class);
    }
}

if (!function_exists('finance_categories')) {
    function finance_categories(): \App\Services\FinanceCategoryService
    {
        return app(\App\Services\FinanceCategoryService::class);
    }
}

if (!function_exists('company_treasury')) {
    function company_treasury(): \App\Services\CompanyTreasuryService
    {
        return app(\App\Services\CompanyTreasuryService::class);
    }
}

if (!function_exists('company_wallet')) {
    function company_wallet(): \App\Services\CompanyWalletService
    {
        return app(\App\Services\CompanyWalletService::class);
    }
}

if (!function_exists('finance_reports')) {
    function finance_reports(): \App\Services\IncomeExpenseReportService
    {
        return app(\App\Services\IncomeExpenseReportService::class);
    }
}

if (!function_exists('http_verify_ssl')) {
    function http_verify_ssl(): bool
    {
        $value = config('ticari.exchange_rates.verify_ssl');

        if ($value === null) {
            return ! app()->environment('local');
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

if (!function_exists('status_label')) {
    function status_label(?string $code, string $group = 'shipment'): string
    {
        if (! $code) {
            return '-';
        }

        $key = "statuses.{$group}.{$code}";
        $label = __($key);

        return $label !== $key ? $label : ucfirst(str_replace('_', ' ', $code));
    }
}

if (!function_exists('task_priority_label')) {
    function task_priority_label(?string $code): string
    {
        if (! $code) {
            return '—';
        }

        $key = "tasks.priorities.{$code}";
        $label = __($key);

        return $label !== $key ? $label : ucfirst($code);
    }
}

if (!function_exists('task_status_label')) {
    function task_status_label(?string $code): string
    {
        if (! $code) {
            return '—';
        }

        $key = "tasks.statuses.{$code}";
        $label = __($key);

        return $label !== $key ? $label : ucfirst(str_replace('_', ' ', $code));
    }
}

if (!function_exists('bilingual_field_label')) {
    function bilingual_field_label(string $translationKey, string $englishLabel): string
    {
        $local = __($translationKey);

        if (app()->getLocale() === 'en') {
            return $englishLabel;
        }

        return $local . ' / ' . $englishLabel;
    }
}

if (!function_exists('vessel_nav_status_display')) {
    function vessel_nav_status_display(?string $status): string
    {
        if (! $status) {
            return '—';
        }

        $normalized = strtolower(trim($status));
        $map = [
            'motorla seyir' => ['tr' => 'Motorla seyir', 'en' => 'Under way using engine'],
            'under way using engine' => ['tr' => 'Motorla seyir', 'en' => 'Under way using engine'],
            'demirde' => ['tr' => 'Demirde', 'en' => 'At anchor'],
            'at anchor' => ['tr' => 'Demirde', 'en' => 'At anchor'],
            'moored' => ['tr' => 'Bağlı', 'en' => 'Moored'],
            'bağlı' => ['tr' => 'Bağlı', 'en' => 'Moored'],
        ];

        foreach ($map as $needle => $labels) {
            if (str_contains($normalized, str_replace(' ', '', $needle)) || str_contains($normalized, $needle)) {
                return $labels['tr'] . ' / ' . $labels['en'];
            }
        }

        if (app()->getLocale() === 'en') {
            return $status;
        }

        return $status . ' / ' . $status;
    }
}

if (!function_exists('type_label')) {
    function type_label(?string $code, string $group): string
    {
        if (! $code) {
            return '—';
        }

        $key = "{$group}.types.{$code}";
        $label = __($key);

        if ($label !== $key) {
            return $label;
        }

        $statusKey = "{$group}.statuses.{$code}";
        $statusLabel = __($statusKey);

        return $statusLabel !== $statusKey ? $statusLabel : ucfirst(str_replace('_', ' ', $code));
    }
}

if (!function_exists('role_label')) {
    function role_label(?string $name): string
    {
        if (! $name) {
            return '—';
        }

        $key = "settings.role_names.{$name}";
        $label = __($key);

        return $label !== $key ? $label : ucfirst(str_replace('-', ' ', $name));
    }
}

if (!function_exists('permission_module_label')) {
    function permission_module_label(string $module): string
    {
        $key = "settings.permission_modules.{$module}";
        $label = __($key);

        return $label !== $key ? $label : ucfirst(str_replace('_', ' ', $module));
    }
}

if (!function_exists('permission_action_label')) {
    function permission_action_label(string $action): string
    {
        $key = "settings.permission_actions.{$action}";
        $label = __($key);

        return $label !== $key ? $label : ucfirst($action);
    }
}

if (!function_exists('permission_label')) {
    function permission_label(string $permission): string
    {
        $parts = explode('.', $permission, 2);

        if (count($parts) !== 2) {
            return $permission;
        }

        return permission_module_label($parts[0]).' — '.permission_action_label($parts[1]);
    }
}

if (!function_exists('document_category_label')) {
    function document_category_label(?string $code): string
    {
        if (! $code) {
            return '—';
        }

        $key = "documents.categories.{$code}";
        $label = __($key);

        return $label !== $key ? $label : ucfirst(str_replace('_', ' ', $code));
    }
}

if (!function_exists('activity_event_label')) {
    function activity_event_label(?string $event): string
    {
        if (! $event) {
            return '—';
        }

        $key = "audit.events.{$event}";
        $label = __($key);

        return $label !== $key ? $label : $event;
    }
}

if (!function_exists('activity_subject_label')) {
    function activity_subject_label(?string $class): string
    {
        if (! $class) {
            return '—';
        }

        $base = class_basename($class);
        $key = "audit.subjects.{$base}";
        $label = __($key);

        return $label !== $key ? $label : $base;
    }
}

if (!function_exists('activity_field_label')) {
    function activity_field_label(string $key): string
    {
        $label = __("audit.fields.{$key}");

        return str_starts_with($label, 'audit.fields.') ? str_replace('_', ' ', $key) : $label;
    }
}

if (!function_exists('activity_format_change_value')) {
    function activity_format_change_value(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? __('audit.yes') : __('audit.no');
        }

        if (is_array($value) || is_object($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE) ?: '…';

            return mb_strlen($encoded) > 160 ? mb_substr($encoded, 0, 157) . '…' : $encoded;
        }

        $string = trim((string) $value);

        if ($string === '') {
            return '—';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $string)) {
            try {
                return \Illuminate\Support\Carbon::parse($string)->format('d.m.Y H:i');
            } catch (\Throwable) {
                // fall through
            }
        }

        return mb_strlen($string) > 160 ? mb_substr($string, 0, 157) . '…' : $string;
    }
}

if (!function_exists('activity_hidden_change_lines')) {
    function activity_hidden_change_lines($log): array
    {
        $lines = [];

        if ((int) ($log->changed_body_html ?? 0) === 1) {
            $lines[] = __('audit.hidden_changes.body_html');
        }

        if ((int) ($log->changed_body_text ?? 0) === 1) {
            $lines[] = __('audit.hidden_changes.body_text');
        }

        if ((int) ($log->changed_credentials ?? 0) === 1) {
            $lines[] = __('audit.hidden_changes.credentials');
        }

        return $lines;
    }
}

if (!function_exists('activity_changes_html')) {
    function activity_changes_html($log): string
    {
        $props = $log->properties ?? [];

        if ($props instanceof \Illuminate\Support\Collection) {
            $props = $props->toArray();
        } elseif (is_string($props)) {
            $props = json_decode($props, true) ?? [];
        }

        if (! is_array($props)) {
            $props = [];
        }

        $old = is_array($props['old'] ?? null) ? $props['old'] : [];
        $new = is_array($props['attributes'] ?? null) ? $props['attributes'] : [];

        $skipKeys = [
            'updated_at', 'created_at', 'password', 'credentials', 'remember_token',
            'body_html', 'body_text', 'to', 'cc', 'bcc',
        ];

        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $lines = [];

        foreach ($keys as $key) {
            if (in_array($key, $skipKeys, true)) {
                continue;
            }

            $from = array_key_exists($key, $old) ? $old[$key] : null;
            $to = array_key_exists($key, $new) ? $new[$key] : null;

            if ($from == $to) {
                continue;
            }

            $lines[] = '<div class="audit-change-line"><span class="audit-change-field">'
                . e(activity_field_label($key)) . ':</span> '
                . '<span class="audit-change-old">' . e(activity_format_change_value($from)) . '</span>'
                . ' <span class="audit-change-arrow">→</span> '
                . '<span class="audit-change-new">' . e(activity_format_change_value($to)) . '</span>'
                . '</div>';
        }

        foreach (activity_hidden_change_lines($log) as $hiddenLine) {
            $lines[] = '<div class="audit-change-line text-muted"><em>' . e($hiddenLine) . '</em></div>';
        }

        if ($lines === []) {
            return '<span class="text-muted">' . e(__('audit.no_changes')) . '</span>';
        }

        return implode('', $lines);
    }
}

if (!function_exists('country_iso2')) {
    function country_iso2(?string $code): string
    {
        if (! $code || strlen(trim($code)) < 2) {
            return '';
        }

        $code = strtoupper(trim($code));

        static $alpha3 = [
            'TUR' => 'TR', 'DEU' => 'DE', 'USA' => 'US', 'GBR' => 'GB', 'ARE' => 'AE',
            'SAU' => 'SA', 'ITA' => 'IT', 'FRA' => 'FR', 'ESP' => 'ES', 'NLD' => 'NL',
            'CHN' => 'CN', 'RUS' => 'RU', 'UKR' => 'UA', 'EGY' => 'EG', 'LBY' => 'LY',
            'IRQ' => 'IQ', 'SYR' => 'SY', 'SDN' => 'SD', 'SWE' => 'SE', 'POL' => 'PL', 'ROU' => 'RO',
            'BGR' => 'BG', 'GRC' => 'GR', 'JOR' => 'JO', 'MAR' => 'MA', 'TUN' => 'TN',
            'DZA' => 'DZ', 'ISR' => 'IL',
        ];

        if (strlen($code) === 3 && isset($alpha3[$code])) {
            return $alpha3[$code];
        }

        return substr($code, 0, 2);
    }
}

if (!function_exists('country_label')) {
    function country_label(?string $code, ?string $locale = null): string
    {
        if (! $code || strlen(trim($code)) < 2) {
            return '';
        }

        $iso2 = country_iso2($code);
        $locale = $locale ?? str_replace('_', '-', app()->getLocale());

        if (extension_loaded('intl')) {
            $name = \Locale::getDisplayRegion('und_' . $iso2, $locale);

            if ($name && $name !== 'und' && strtoupper($name) !== $iso2) {
                return $name;
            }
        }

        $fallback = __("countries.{$iso2}");

        return str_starts_with($fallback, 'countries.') ? $code : $fallback;
    }
}

if (!function_exists('country_options')) {
    /** @return array<string, string> ISO2 => localized name */
    function country_options(?string $locale = null): array
    {
        $options = trans('countries', [], $locale);
        if (! is_array($options)) {
            return [];
        }
        asort($options);

        return $options;
    }
}

if (!function_exists('trade_currency')) {
    function trade_currency(): string
    {
        return strtoupper(config('ticari.trade_currency', 'USD'));
    }
}

if (!function_exists('fx_snapshot_rates')) {
    function fx_snapshot_rates(): array
    {
        return app(\App\Services\ExchangeRateService::class)->snapshotRates();
    }
}

if (!function_exists('format_money_dual')) {
    /** USD + TRY birlikte göster (kur otomatik). */
    function format_money_dual(float $usd, float $try, int $decimals = 0): string
    {
        $primary = format_money($usd, 'USD', $decimals);
        $secondary = format_money($try, 'TRY', $decimals);

        return $primary . '<span class="dual-money-sep"> · </span><span class="text-muted">' . $secondary . '</span>';
    }
}

if (!function_exists('format_money')) {
    function format_money(float $amount, ?string $currency = null, int $decimals = 0): string
    {
        $currency = strtoupper($currency ?? (registry()->defaultCurrency()?->code ?? 'TRY'));
        $formatted = number_format($amount, $decimals, ',', '.');

        return match ($currency) {
            'TRY' => $formatted . ' ₺',
            'USD' => $formatted . ' USD',
            'EUR' => $formatted . ' EUR',
            'GBP' => $formatted . ' GBP',
            default => $formatted . ' ' . $currency,
        };
    }
}

if (!function_exists('format_try_equivalent')) {
    function format_try_equivalent(float $amount, string $currency, ?float $exchangeRate = null): ?string
    {
        $currency = strtoupper($currency);
        $default = registry()->defaultCurrency()?->code ?? 'TRY';

        if ($currency === $default) {
            return null;
        }

        try {
            $rate = app(\App\Services\ExchangeRateService::class)
                ->rateToDefaultCurrency($currency, $exchangeRate);
            $try = round($amount * $rate, 2);

            return number_format($try, 2, ',', '.') . ' ₺';
        } catch (\Throwable) {
            return null;
        }
    }
}

if (!function_exists('port_display_label')) {
    function port_display_label(mixed $portOrHint = null, ?\App\Models\Port $port = null): ?string
    {
        $resolver = app(\App\Services\PortResolver::class);

        if ($portOrHint instanceof \App\Models\Port) {
            return $resolver->label($portOrHint);
        }

        if ($port) {
            return $resolver->label($port);
        }

        return is_string($portOrHint) && trim($portOrHint) !== ''
            ? $resolver->label(null, $portOrHint)
            : null;
    }
}

if (!function_exists('default_port_type_for_mode')) {
    function default_port_type_for_mode(string $mode): string
    {
        return match ($mode) {
            'road' => 'land',
            'air' => 'air',
            'rail' => 'rail',
            'sea', 'multimodal' => 'sea',
            default => 'sea',
        };
    }
}

if (!function_exists('port_type_label')) {
    function port_type_label(string $type): string
    {
        $label = __('logistics.port_types.' . $type);

        return str_starts_with($label, 'logistics.port_types.') ? $type : $label;
    }
}

if (!function_exists('shipment_location_label')) {
    function shipment_location_label(string $mode, string $direction = 'origin'): string
    {
        return match ($mode) {
            'road', 'multimodal' => $direction === 'origin'
                ? __('logistics.origin_border')
                : __('logistics.destination_border'),
            'air' => $direction === 'origin'
                ? __('logistics.origin_airport')
                : __('logistics.destination_airport'),
            'rail' => $direction === 'origin'
                ? __('logistics.origin_station')
                : __('logistics.destination_station'),
            default => $direction === 'origin'
                ? __('logistics.origin_port')
                : __('logistics.destination_port'),
        };
    }
}

if (!function_exists('port_types_for_mode')) {
    /** @return list<string> */
    function port_types_for_mode(string $mode): array
    {
        return match ($mode) {
            'road' => ['land', 'road'],
            'air' => ['air'],
            'rail' => ['rail', 'land'],
            'sea', 'multimodal' => ['sea', 'land'],
            default => ['sea', 'air', 'rail', 'land', 'road'],
        };
    }
}

if (!function_exists('incoterm_label')) {
    function incoterm_label(?string $code): string
    {
        if (! $code) {
            return '-';
        }

        $name = __("incoterms.{$code}.name");

        return str_starts_with($name, 'incoterms.') ? $code : "{$code} — {$name}";
    }
}

if (!function_exists('incoterm_description')) {
    function incoterm_description(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        $desc = __("incoterms.{$code}.desc");

        return str_starts_with($desc, 'incoterms.') ? null : $desc;
    }
}

if (!function_exists('shipment_status_display')) {
    function shipment_status_display(\App\Models\Shipment|string $shipment, ?string $location = null): string
    {
        if ($shipment instanceof \App\Models\Shipment) {
            $status = $shipment->status;
            $location = $shipment->status_location;
        } else {
            $status = $shipment;
        }

        $label = status_label($status, 'shipment');
        $location = trim((string) $location);

        return $location !== '' ? "{$label} · {$location}" : $label;
    }
}

if (!function_exists('shipment_statuses_rule')) {
    function shipment_statuses_rule(): string
    {
        return 'in:' . implode(',', config('ticari.shipment_statuses', []));
    }
}

if (!function_exists('shipment_next_statuses')) {
    function shipment_next_statuses(string $transportMode, string $current): array
    {
        $map = config("ticari.shipment_transitions.{$transportMode}")
            ?? config('ticari.shipment_transitions.default', []);

        return $map[$current] ?? [];
    }
}

if (!function_exists('site_branding')) {
    function site_branding(): \App\Services\SiteBrandingService
    {
        return app(\App\Services\SiteBrandingService::class);
    }
}

if (!function_exists('app_timezone')) {
    function app_timezone(): string
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            $tz = \App\Models\Setting::get('timezone');

            if ($tz) {
                return $tz;
            }
        }

        return 'Europe/Istanbul';
    }
}

if (!function_exists('app_brand')) {
    function app_brand(): string
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            $company = \App\Models\Setting::get('company_name');

            if ($company) {
                return $company;
            }
        }

        return config('app.name', 'Kurtulum İç ve Dış Ticaret');
    }
}

if (!function_exists('user_avatar_url')) {
    function user_avatar_url(?\App\Models\User $user = null): ?string
    {
        $user ??= auth()->user();

        return site_branding()->userAvatarUrl($user);
    }
}

if (!function_exists('user_avatar_initials')) {
    function user_avatar_initials(?\App\Models\User $user = null): string
    {
        $user ??= auth()->user();

        return site_branding()->userInitials($user);
    }
}

if (!function_exists('currency_name')) {
    function currency_name(string $code): string
    {
        $key = "currencies.{$code}";
        $label = __($key);

        if ($label !== $key) {
            return $label;
        }

        $fromDb = registry()->currencies()->firstWhere('code', $code)?->name;

        return $fromDb ?: $code;
    }
}
