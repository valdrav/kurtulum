@extends('layouts.app')
@section('title', $employee->exists ? __('app.edit') : __('app.create'))

@section('content')
<div class="page-header d-print-none">
    <div class="row align-items-center">
        <div class="col"><h2 class="page-title">{{ $employee->exists ? __('app.edit') : __('app.create') }} — {{ __('app.employees') }}</h2></div>
    </div>
</div>

<form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}" x-data="{ createUser: {{ old('create_user_account', false) ? 'true' : 'false' }} }">
    @csrf
    @if($employee->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('settings.personal_info') }}</h3></div>
                <div class="card-body row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ad</label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $employee->first_name) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Soyad</label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $employee->last_name) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('settings.employment_info') }}</h3></div>
                <div class="card-body row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('settings.employee_code') }}</label>
                        <input type="text" name="employee_code" class="form-control" value="{{ old('employee_code', $employee->employee_code) }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('settings.position') }}</label>
                        <input type="text" name="position" class="form-control" value="{{ old('position', $employee->position) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('settings.hire_date') }}</label>
                        <input type="date" name="hire_date" class="form-control" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('settings.departments') }}</label>
                        <select name="department_id" class="form-select">
                            <option value="">—</option>
                            @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected(old('department_id', $employee->department_id) == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('app.status') }}</label>
                        <select name="status" class="form-select" required>
                            @foreach(['active','inactive','on_leave'] as $st)
                            <option value="{{ $st }}" @selected(old('status', $employee->status ?? 'active') === $st)>{{ __("settings.{$st}") }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('settings.system_access') }}</h3></div>
                <div class="card-body">
                    @if($employee->user)
                    <div class="alert alert-success mb-3">
                        <i class="ti ti-link me-1"></i>{{ __('settings.link_user') }}: <strong>{{ $employee->user->email }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('settings.user_role') }}</label>
                        <select name="user_role" class="form-select">
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('user_role', $employee->user->roles->first()?->name) === $role->name)>{{ role_label($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <label class="form-check form-switch mb-3">
                        <input type="checkbox" name="create_user_account" class="form-check-input" value="1" x-model="createUser">
                        <span class="form-check-label">{{ __('settings.create_user_account') }}</span>
                    </label>
                    <div x-show="createUser" x-cloak>
                        <div class="mb-3">
                            <label class="form-label">{{ __('settings.user_password') }}</label>
                            <input type="password" name="user_password" class="form-control" minlength="8">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('settings.user_role') }}</label>
                            <select name="user_role" class="form-select">
                                @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ role_label($role->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
