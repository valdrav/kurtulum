<?php

namespace App\Http\Middleware;

use App\Services\PortalContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isPortalUser()) {
            abort(403, 'Bu alan yalnızca müşteri portalı kullanıcıları içindir.');
        }

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')->withErrors(['email' => __('auth.inactive')]);
        }

        $access = $user->portalAccess()->with('customer')->first();

        if (! $access || ! $access->is_active || ! $access->customer) {
            auth()->logout();

            return redirect()->route('login')->withErrors(['email' => 'Portal erişiminiz devre dışı bırakılmış.']);
        }

        app()->instance(PortalContextService::class, new PortalContextService($user, $access));

        return $next($request);
    }
}
