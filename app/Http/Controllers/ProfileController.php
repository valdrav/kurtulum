<?php

namespace App\Http\Controllers;

use App\Services\SiteBrandingService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        protected SiteBrandingService $branding
    ) {}

    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $validLocales = registry()->languageCodes();
        if (empty($validLocales)) {
            $validLocales = array_keys(config('ticari.locales', []));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'locale' => 'required|in:' . implode(',', $validLocales),
            'theme' => 'required|in:light,dark',
            'password' => 'nullable|string|min:8|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,webp,gif|max:2048',
            'remove_avatar' => 'nullable|boolean',
        ]);

        if ($request->boolean('remove_avatar')) {
            $this->branding->deleteUserAvatar($user);
        } elseif ($request->hasFile('avatar')) {
            $this->branding->storeUserAvatar($user, $request->file('avatar'));
        }

        if (! empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        unset($validated['avatar'], $validated['remove_avatar']);

        $user->update($validated);
        session(['locale' => $validated['locale'], 'theme' => $validated['theme']]);

        return back()->with('success', __('messages.saved'));
    }
}
