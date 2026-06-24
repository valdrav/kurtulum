<?php

namespace App\Http\Controllers;

use App\Services\SiteBrandingService;
use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    public function __invoke(SiteBrandingService $branding): JsonResponse
    {
        return response()->json($branding->manifest(), 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
