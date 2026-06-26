@extends('layouts.app')
@section('title', $employee->full_name)

@section('content')
<div class="page-header d-print-none">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">{{ $employee->full_name }}</h2>
            <div class="text-muted">{{ $employee->employee_code }} · {{ $employee->position ?? '—' }}</div>
        </div>
        <div class="col-auto btn-list">
            @if(can_access('employees.edit'))
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">{{ __('app.edit') }}</a>
            @endif
            @if(can_access('employees.delete'))
            <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="d-inline"
                  data-confirm="{{ __('app.confirm_delete') }}">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger">{{ __('app.delete') }}</button>
            </form>
            @endif
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <span class="avatar avatar-xl bg-primary-lt avatar-ring mb-3">{{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}</span>
                <h3>{{ $employee->full_name }}</h3>
                <p class="text-muted">{{ $employee->position }}</p>
                <span class="badge badge-status-{{ $employee->status }}">{{ __("settings.{$employee->status}") }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item"><div class="datagrid-title">E-posta</div><div class="datagrid-content">{{ $employee->email ?? '—' }}</div></div>
                    <div class="datagrid-item"><div class="datagrid-title">Telefon</div><div class="datagrid-content">{{ $employee->phone ?? '—' }}</div></div>
                    <div class="datagrid-item"><div class="datagrid-title">{{ __('settings.departments') }}</div><div class="datagrid-content">{{ $employee->department?->name ?? '—' }}</div></div>
                    <div class="datagrid-item"><div class="datagrid-title">{{ __('settings.hire_date') }}</div><div class="datagrid-content">{{ $employee->hire_date?->format('d.m.Y') ?? '—' }}</div></div>
                    <div class="datagrid-item"><div class="datagrid-title">{{ __('settings.link_user') }}</div>
                        <div class="datagrid-content">
                            @if($employee->user)
                            {{ $employee->user->email }}
                            <span class="badge bg-azure-lt ms-1">{{ role_label($employee->user->roles->first()?->name) }}</span>
                            @else — @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
