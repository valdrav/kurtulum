<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.edit');
    }

    public function index()
    {
        $roles = Role::withCount('permissions', 'users')->orderBy('name')->get();

        return view('settings.roles.index', compact('roles'));
    }

    public function edit(Role $role)
    {
        if ($role->name === 'super-admin' && !auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $permissions = Permission::orderBy('name')->get()->groupBy(function ($p) {
            return explode('.', $p->name)[0];
        });

        $rolePermissions = $role->permissions->pluck('name')->all();

        return view('settings.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === 'super-admin') {
            return back()->with('success', __('settings.super_admin_locked'));
        }

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('settings.roles.index')->with('success', __('messages.updated'));
    }
}
