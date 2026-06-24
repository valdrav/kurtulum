<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employees.view')->only(['index', 'show']);
        $this->middleware('permission:employees.create')->only(['create', 'store']);
        $this->middleware('permission:employees.edit')->only(['edit', 'update']);
        $this->middleware('permission:employees.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $employees = Employee::with(['department', 'user.roles'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            }))
            ->when($request->department_id, fn ($q, $d) => $q->where('department_id', $d))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('employees.index', compact('employees', 'departments'));
    }

    public function create()
    {
        return view('employees.form', [
            'employee' => new Employee(['status' => 'active']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateEmployee($request);

        DB::transaction(function () use ($request, $validated) {
            $employee = Employee::create($validated);

            if ($request->boolean('create_user_account') && !empty($validated['email'])) {
                $user = User::create([
                    'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                    'email' => $validated['email'],
                    'password' => Hash::make($request->input('user_password', 'password123')),
                    'department_id' => $validated['department_id'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'locale' => 'tr',
                    'theme' => 'light',
                    'is_active' => $validated['status'] === 'active',
                    'email_verified_at' => now(),
                ]);

                if ($request->filled('user_role')) {
                    $user->assignRole($request->input('user_role'));
                }

                $employee->update(['user_id' => $user->id]);
            }
        });

        return redirect()->route('employees.index')->with('success', __('messages.created'));
    }

    public function show(Employee $employee)
    {
        $employee->load(['department', 'user.roles']);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $employee->load('user.roles');

        return view('employees.form', [
            'employee' => $employee,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $this->validateEmployee($request, $employee);
        $employee->update($validated);

        if ($employee->user) {
            $employee->user->update([
                'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                'email' => $validated['email'] ?? $employee->user->email,
                'department_id' => $validated['department_id'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['status'] === 'active',
            ]);

            if ($request->filled('user_role')) {
                $employee->user->syncRoles([$request->input('user_role')]);
            }
        }

        return redirect()->route('employees.show', $employee)->with('success', __('messages.updated'));
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('success', __('messages.deleted'));
    }

    protected function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'employee_code' => 'required|string|unique:employees,employee_code,' . ($employee?->id ?? 'NULL'),
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,on_leave',
            'create_user_account' => 'nullable|boolean',
            'user_password' => 'nullable|string|min:8',
            'user_role' => 'nullable|exists:roles,name',
        ]);
    }
}
