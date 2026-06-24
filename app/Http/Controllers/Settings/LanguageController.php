<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemLanguage;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index()
    {
        $languages = SystemLanguage::orderBy('sort_order')->get();
        return view('settings.languages.index', compact('languages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:system_languages,code',
            'name' => 'required|string|max:100',
            'native_name' => 'nullable|string|max:100',
            'direction' => 'required|in:ltr,rtl',
            'flag' => 'nullable|string|max:10',
        ]);

        $validated['sort_order'] = SystemLanguage::max('sort_order') + 1;
        SystemLanguage::create($validated);

        return back()->with('success', __('extensions.language_added'));
    }

    public function update(Request $request, SystemLanguage $language)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'native_name' => 'nullable|string|max:100',
            'direction' => 'required|in:ltr,rtl',
            'flag' => 'nullable|string|max:10',
            'is_active' => 'boolean',
        ]);

        $language->update($validated);

        if ($request->boolean('is_default')) {
            $language->setAsDefault();
        }

        return back()->with('success', __('messages.updated'));
    }

    public function destroy(SystemLanguage $language)
    {
        if ($language->is_default) {
            return back()->withErrors(['language' => __('extensions.cannot_delete_default')]);
        }

        $language->delete();
        return back()->with('success', __('messages.deleted'));
    }
}
