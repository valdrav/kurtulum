<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?? session('locale')
            ?? config('ticari.default_locale');

        if (Schema::hasTable('system_languages')) {
            try {
                $validCodes = registry()->languageCodes();

                if (! empty($validCodes) && ! in_array($locale, $validCodes, true)) {
                    $locale = registry()->defaultLanguage()?->code ?? config('ticari.default_locale');
                }

                $lang = registry()->languages()->firstWhere('code', $locale);
                view()->share('currentLanguage', $lang);
                view()->share('isRtl', $lang?->direction === 'rtl');
            } catch (\Throwable) {
                view()->share('isRtl', false);
            }
        } else {
            $fallback = config('ticari.locales.' . $locale . '.dir', 'ltr');
            view()->share('isRtl', $fallback === 'rtl');
        }

        if (! is_dir(lang_path($locale))) {
            $locale = config('app.fallback_locale', 'en');
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }
}
