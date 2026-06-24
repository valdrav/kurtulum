@extends('layouts.app')
@section('title', __('app.employees'))

@section('content')
@include('partials.page-header', ['title' => __('app.employees'), 'createRoute' => route('employees.create'), 'createPermission' => 'employees.create'])

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2">
            <div class="col-12 col-md-4"><input type="text" name="search" class="form-control" placeholder="{{ __('app.search') }}..." value="{{ request('search') }}"></div>
            <div class="col-6 col-md-3">
                <select name="department_id" class="form-select">
                    <option value="">{{ __('app.all') }} {{ __('settings.departments') }}</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select">
                    <option value="">{{ __('app.all') }} {{ __('app.status') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('settings.active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('settings.inactive') }}</option>
                    <option value="on_leave" @selected(request('status') === 'on_leave')>{{ __('settings.on_leave') }}</option>
                </select>
            </div>
            <div class="col-12 col-md-2"><button class="btn btn-outline-primary w-100">{{ __('app.filter') }}</button></div>
        </form>
    </div>
</div>

<div class="d-md-none ef-mobile-list mb-3">
    @forelse($employees as $e)
    @include('partials.mobile-record-card', [
        'url' => route('employees.show', $e),
        'title' => $e->full_name,
        'subtitle' => $e->position ?? '—',
        'meta' => ($e->department?->name ?? '—').' · '.$e->employee_code,
        'badge' => __("settings.{$e->status}") ?? $e->status,
        'editUrl' => route('employees.edit', $e),
        'editPermission' => 'employees.edit',
    ])
    @empty
    <div class="card"><div class="card-body text-muted">{{ __('app.no_records') }}</div></div>
    @endforelse
</div>

<div class="card hide-mobile">
    <div class="table-responsive">
        <table class="table table-vcenter table-modern card-table">
            <thead>
                <tr>
                    <th>{{ __('settings.employee_code') }}</th>
                    <th>Ad Soyad</th>
                    <th>{{ __('settings.position') }}</th>
                    <th>{{ __('settings.departments') }}</th>
                    <th>{{ __('settings.system_access') }}</th>
                    <th>{{ __('app.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $e)
                <tr>
                    <td><code>{{ $e->employee_code }}</code></td>
                    <td>
                        <a href="{{ route('employees.show', $e) }}" class="text-reset fw-semibold">{{ $e->full_name }}</a>
                        @if($e->email)<div class="text-muted small">{{ $e->email }}</div>@endif
                    </td>
                    <td>{{ $e->position ?? '—' }}</td>
                    <td>{{ $e->department?->name ?? '—' }}</td>
                    <td>
                        @if($e->user)
                        <span class="badge bg-green-lt"><i class="ti ti-check me-1"></i>{{ role_label($e->user->roles->first()?->name) }}</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td><span class="badge badge-status-{{ $e->status }}">{{ __("settings.{$e->status}") ?? $e->status }}</span></td>
                    <td>
                        @if(can_access('employees.edit'))
                        <a href="{{ route('employees.edit', $e) }}" class="btn btn-sm btn-outline-primary">{{ __('app.edit') }}</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())<div class="card-footer">{{ $employees->links() }}</div>@endif
</div>
@if($employees->hasPages())<div class="d-md-none mt-2">{{ $employees->links() }}</div>@endif
@endsection
