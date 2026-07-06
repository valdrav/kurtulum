<?php

namespace App\Http\Middleware;

use App\Models\CustomerPortalAccess;
use App\Services\PortalContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->usesCustomerPortal()) {
            abort(403, 'Bu alan yalnızca müşteri portalı kullanıcıları içindir.');
        }

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')->withErrors(['email' => __('auth.inactive')]);
        }

        $access = CustomerPortalAccess::query()
            ->with('customer')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (! $access || ! $access->customer) {
            auth()->logout();

            return redirect()->route('login')->withErrors(['email' => 'Portal erişiminiz devre dışı bırakılmış.']);
        }

        if ((int) $user->customer_id !== (int) $access->customer_id) {
            $user->forceFill([
                'user_type' => 'portal',
                'customer_id' => $access->customer_id,
            ])->save();
        }

        app()->instance(PortalContextService::class, new PortalContextService($user, $access));

        return $next($request);
    }
}
