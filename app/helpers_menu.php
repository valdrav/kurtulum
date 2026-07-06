<?php

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
