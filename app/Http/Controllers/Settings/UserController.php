<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users.view')->only(['index', 'edit']);
        $this->middleware('permission:users.create')->only(['create', 'store']);
        $this->middleware('permission:users.edit')->only(['update', 'toggleActive']);
        $this->middleware('permission:users.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $users = User::with(['roles', 'department'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            }))
            ->when($request->role, fn ($q, $r) => $q->role($r))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('settings.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('settings.users.form', [
            'user' => new User(['is_active' => true, 'locale' => 'tr', 'theme' => 'light']),
            'roles' => $roles,
            'departments' => $departments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateUser($request);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'locale' => $validated['locale'] ?? 'tr',
            'theme' => $validated['theme'] ?? 'light',
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('settings.users.index')->with('success', __('messages.created'));
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('settings.users.form', compact('user', 'roles', 'departments'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->id === auth()->id() && !$request->boolean('is_active')) {
            return back()->withErrors(['is_active' => __('settings.cannot_deactivate_self')]);
        }

        $validated = $this->validateUser($request, $user);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'locale' => $validated['locale'] ?? 'tr',
            'theme' => $validated['theme'] ?? 'light',
            'is_active' => $request->boolean('is_active'),
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('settings.users.index')->with('success', __('messages.updated'));
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => __('settings.cannot_delete_self')]);
        }

        $user->delete();

        return back()->with('success', __('messages.deleted'));
    }

    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => __('settings.cannot_deactivate_self')]);
        }

        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', __('messages.updated'));
    }

    protected function validateUser(Request $request, ?User $user = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role' => 'required|exists:roles,name',
            'phone' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'locale' => 'required|in:tr,en,ar',
            'theme' => 'required|in:light,dark',
        ];

        return $request->validate($rules);
    }
}
