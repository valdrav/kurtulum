<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::orderBy('sort_order')->get();
        return view('settings.payment-methods.index', compact('methods'));
    }

    public function create()
    {
        return view('settings.payment-methods.form', ['method' => new PaymentMethod()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateMethod($request);
        $validated['sort_order'] = PaymentMethod::max('sort_order') + 1;
        PaymentMethod::create($validated);

        return redirect()->route('settings.payment-methods.index')->with('success', __('extensions.payment_method_added'));
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        return view('settings.payment-methods.form', ['method' => $paymentMethod]);
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update($this->validateMethod($request, $paymentMethod));
        return redirect()->route('settings.payment-methods.index')->with('success', __('messages.updated'));
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();
        return back()->with('success', __('messages.deleted'));
    }

    public function fields(PaymentMethod $paymentMethod)
    {
        return response()->json([
            'method' => $paymentMethod->only(['id', 'code', 'name', 'requires_reference', 'fee_type', 'fee_amount']),
            'fields' => $paymentMethod->getFormFields(),
            'currencies' => payment_methods()->supportedCurrencies($paymentMethod),
            'features' => $paymentMethod->features ?? [],
        ]);
    }

    protected function validateMethod(Request $request, ?PaymentMethod $existing = null): array
    {
        $codeRule = 'required|string|max:50|unique:payment_methods,code';
        if ($existing) {
            $codeRule .= ',' . $existing->id;
        }

        $validated = $request->validate([
            'code' => $codeRule,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:payment,collection,both',
            'icon' => 'nullable|string|max:50',
            'fee_type' => 'required|in:none,fixed,percent',
            'fee_amount' => 'nullable|numeric|min:0',
            'requires_reference' => 'boolean',
            'requires_bank_account' => 'boolean',
            'is_online' => 'boolean',
            'is_active' => 'boolean',
            'config_schema_json' => 'nullable|string',
            'features_json' => 'nullable|string',
            'supported_currencies_json' => 'nullable|string',
        ]);

        $validated['config_schema'] = $validated['config_schema_json']
            ? json_decode($validated['config_schema_json'], true) : null;
        $validated['features'] = $validated['features_json']
            ? json_decode($validated['features_json'], true) : [];
        $validated['supported_currencies'] = $validated['supported_currencies_json']
            ? json_decode($validated['supported_currencies_json'], true) : null;

        unset($validated['config_schema_json'], $validated['features_json'], $validated['supported_currencies_json']);

        return $validated;
    }
}
