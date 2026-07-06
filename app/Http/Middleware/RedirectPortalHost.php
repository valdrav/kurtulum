<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPortalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $portalHost = config('ticari.portal.host');
        $currentHost = $request->getHost();

        if ($portalHost && $currentHost === $portalHost && ! $request->is('portal*') && ! $request->is('login') && ! $request->is('logout')) {
            if ($request->user()?->isPortalUser()) {
                return redirect()->route('portal.dashboard');
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
