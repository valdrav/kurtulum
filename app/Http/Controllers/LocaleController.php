<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        $validCodes = registry()->languageCodes();

        if (! empty($validCodes) && ! in_array($locale, $validCodes, true)) {
            abort(404);
        }

        if (! is_dir(lang_path($locale))) {
            abort(404);
        }

        session(['locale' => $locale]);

        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
            $request->user()->locale = $locale;
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return back();
    }
}
