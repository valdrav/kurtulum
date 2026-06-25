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

if (!function_exists('activity_format_change_value')) {
    function activity_format_change_value(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE) ?: '…';

            return mb_strlen($encoded) > 120 ? mb_substr($encoded, 0, 117) . '…' : $encoded;
        }

        $string = (string) $value;

        return mb_strlen($string) > 120 ? mb_substr($string, 0, 117) . '…' : $string;
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
            return '<span class="text-muted">' . e(__('audit.no_changes')) . '</span>';
        }

        $old = $props['old'] ?? [];
        $new = $props['attributes'] ?? [];

        if (empty($old) && empty($new)) {
            return '<span class="text-muted">' . e(__('audit.no_changes')) . '</span>';
        }

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

            $from = $old[$key] ?? '—';
            $to = $new[$key] ?? '—';

            if ($from == $to) {
                continue;
            }

            $lines[] = '<div class="small text-break"><strong>' . e($key) . ':</strong> '
                . e(activity_format_change_value($from))
                . ' → '
                . e(activity_format_change_value($to))
                . '</div>';
        }

        if ($lines === [] && (! empty($old) || ! empty($new))) {
            return '<span class="text-muted">' . e(__('audit.summary_only')) . '</span>';
        }

        return $lines ? implode('', $lines) : '<span class="text-muted">' . e(__('audit.no_changes')) . '</span>';
    }
}

if (!function_exists('country_label')) {
    function country_label(?string $code, ?string $locale = null): string
    {
        if (! $code || strlen(trim($code)) < 2) {
            return '';
        }

        $code = strtoupper(substr(trim($code), 0, 2));
        $locale = $locale ?? str_replace('_', '-', app()->getLocale());

        if (extension_loaded('intl')) {
            $name = \Locale::getDisplayRegion('und_' . $code, $locale);

            if ($name && $name !== 'und' && strtoupper($name) !== $code) {
                return $name;
            }
        }

        $fallback = __("countries.{$code}");

        return str_starts_with($fallback, 'countries.') ? $code : $fallback;
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
