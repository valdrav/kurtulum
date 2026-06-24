<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = filter_var(config('ticari.installed'), FILTER_VALIDATE_BOOLEAN);

        if (!$installed && !$request->is('install', 'install/*')) {
            return redirect()->route('install.welcome');
        }

        if ($installed && $request->is('install', 'install/*')) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
