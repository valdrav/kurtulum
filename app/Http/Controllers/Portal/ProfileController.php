<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit()
    {
        abort_unless(portal()->allows('edit_profile'), 403);

        $customer = portal()->customer();

        return view('portal.profile', compact('customer'));
    }

    public function update(Request $request)
    {
        abort_unless(portal()->allows('edit_profile'), 403);

        $customer = portal()->customer();

        $validated = $request->validate([
            'contact_person' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:2000',
        ]);

        $customer->update($validated);

        if ($request->user()) {
            $request->user()->update([
                'name' => $customer->company_name,
                'email' => $validated['email'] ?? $request->user()->email,
                'phone' => $validated['phone'] ?? $request->user()->phone,
            ]);
        }

        return back()->with('success', __('messages.updated'));
    }
}
