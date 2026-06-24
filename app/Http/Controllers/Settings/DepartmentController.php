<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.view')->only(['index']);
        $this->middleware('permission:settings.edit')->only(['store', 'update', 'destroy']);
    }

    public function index()
    {
        $departments = Department::with(['manager', 'parent'])
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        $managers = User::where('is_active', true)->orderBy('name')->get();

        return view('settings.departments.index', compact('departments', 'managers'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateDepartment($request);
        Department::create($validated);

        return back()->with('success', __('messages.created'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $this->validateDepartment($request, $department);
        $department->update($validated);

        return back()->with('success', __('messages.updated'));
    }

    public function destroy(Department $department)
    {
        if ($department->employees()->exists()) {
            return back()->withErrors(['department' => __('settings.department_has_employees')]);
        }

        $department->delete();

        return back()->with('success', __('messages.deleted'));
    }

    protected function validateDepartment(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_user_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
