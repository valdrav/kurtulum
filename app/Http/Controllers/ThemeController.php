<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function switch(Request $request, string $theme)
    {
        if (!in_array($theme, ['light', 'dark'])) {
            abort(404);
        }

        session(['theme' => $theme]);
        if ($request->user()) {
            $request->user()->update(['theme' => $theme]);
        }

        return back();
    }
}
