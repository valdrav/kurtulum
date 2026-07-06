<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isPortalUser()) {
            if ($request->expectsJson()) {
                abort(403, 'Portal kullanıcıları bu alana erişemez.');
            }

            return redirect()->route('portal.dashboard');
        }

        return $next($request);
    }
}
