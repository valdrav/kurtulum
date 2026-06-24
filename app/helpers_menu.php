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
