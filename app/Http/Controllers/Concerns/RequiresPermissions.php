<?php

namespace App\Http\Controllers\Concerns;

trait RequiresPermissions
{
    protected function registerPermissions(array $map): void
    {
        foreach ($map as $methods => $permission) {
            $this->middleware('permission:' . $permission)->only(
                is_array($methods) ? $methods : explode('|', $methods)
            );
        }
    }
}
