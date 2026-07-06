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
