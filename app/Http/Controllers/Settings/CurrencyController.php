<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemCurrency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = SystemCurrency::orderBy('sort_order')->get();
        return view('settings.currencies.index', compact('currencies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:system_currencies,code',
            'name' => 'required|string|max:100',
            'symbol' => 'nullable|string|max:10',
            'decimal_places' => 'required|integer|min:0|max:8',
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        $validated['sort_order'] = SystemCurrency::max('sort_order') + 1;
        SystemCurrency::create($validated);

        return back()->with('success', __('extensions.currency_added'));
    }

    public function update(Request $request, SystemCurrency $currency)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'symbol' => 'nullable|string|max:10',
            'decimal_places' => 'required|integer|min:0|max:8',
            'exchange_rate' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $currency->update($validated);

        if ($request->boolean('is_default')) {
            $currency->setAsDefault();
        }

        return back()->with('success', __('messages.updated'));
    }

    public function destroy(SystemCurrency $currency)
    {
        if ($currency->is_default) {
            return back()->withErrors(['currency' => __('extensions.cannot_delete_default')]);
        }

        $currency->delete();
        return back()->with('success', __('messages.deleted'));
    }
}
