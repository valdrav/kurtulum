<?php

namespace App\Http\Middleware;

use App\Models\ExternalApiConnection;
use App\Models\Setting;
use App\Services\ExternalApiConnectionService;
use App\Services\ExternalApiContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateExternalApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::get('external_api_enabled', '0') !== '1') {
            return response()->json(['message' => 'External API is disabled.'], 503);
        }

        $token = $request->bearerToken();

        if (! is_string($token) || ! str_starts_with($token, 'ef_')) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $hash = app(ExternalApiConnectionService::class)->hashToken($token);

        $connection = ExternalApiConnection::query()
            ->with('customer')
            ->where('token_hash', $hash)
            ->where('is_active', true)
            ->first();

        if (! $connection || ! $connection->customer || $connection->customer->status !== 'active') {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $connection->forceFill(['last_used_at' => now()])->saveQuietly();

        app()->instance(ExternalApiContextService::class, new ExternalApiContextService($connection));

        return $next($request);
    }
}
