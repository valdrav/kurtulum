<?php

if (! function_exists('is_patron_or_super_admin')) {
    function is_patron_or_super_admin(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasRole('patron') || $user->hasRole('super-admin'));
    }
}

if (!function_exists('can_access')) {
    function can_access(string $permission): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($permission === 'hr.access') {
            return $user->hasRole('patron');
        }

        if ($permission === 'schedules.access') {
            return true;
        }

        return $user->can($permission);
    }
}

if (! function_exists('is_super_admin')) {
    function is_super_admin(): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }
}

if (! function_exists('navbar')) {
    function navbar(): \App\Services\NavbarConfigService
    {
        return app(\App\Services\NavbarConfigService::class);
    }
}

if (! function_exists('portal')) {
    function portal(): \App\Services\PortalContextService
    {
        return app(\App\Services\PortalContextService::class);
    }
}

if (! function_exists('whatsapp_url')) {
    function whatsapp_url(?string $phone, ?string $message = null): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '90'.substr($digits, 1);
        } elseif (strlen($digits) === 10) {
            $digits = '90'.$digits;
        }

        $url = 'https://wa.me/'.$digits;

        if ($message) {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }
}
