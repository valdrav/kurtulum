<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        $theme = $request->user()?->theme
            ?? session('theme')
            ?? config('ticari.default_theme');

        if (!in_array($theme, ['light', 'dark'])) {
            $theme = 'light';
        }

        session(['theme' => $theme]);
        view()->share('currentTheme', $theme);

        return $next($request);
    }
}
