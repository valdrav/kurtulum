@extends('layouts.app')
@section('title', __('hr.title'))

@section('content')
@include('partials.page-header', ['title' => __('hr.title'), 'subtitle' => __('hr.subtitle'), 'createRoute' => route('hr.create')])

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

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern">
            <thead>
                <tr>
                    <th>{{ __('settings.employee_code') }}</th>
                    <th>Ad Soyad</th>
                    <th>{{ __('settings.position') }}</th>
                    <th>{{ __('settings.departments') }}</th>
                    <th>{{ __('hr.base_salary') }}</th>
                    <th>{{ __('app.status') }}</th>
                    <th class="ef-table-actions"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $e)
                <tr>
                    <td>{{ $e->employee_code }}</td>
                    <td><a href="{{ route('hr.show', $e) }}" class="fw-semibold">{{ $e->full_name }}</a></td>
                    <td>{{ $e->position ?? '—' }}</td>
                    <td>{{ $e->department?->name ?? '—' }}</td>
                    <td>
                        @if($e->hrDetail?->base_salary)
                        {{ number_format($e->hrDetail->base_salary, 2, ',', '.') }} {{ $e->hrDetail->salary_currency }}
                        @else — @endif
                    </td>
                    <td>{{ __("settings.{$e->status}") ?? $e->status }}</td>
                    <td class="ef-table-actions text-nowrap text-end">
                        <a href="{{ route('hr.show', $e) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-eye"></i></a>
                        <a href="{{ route('hr.edit', $e) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())<div class="card-footer d-flex justify-content-center">{{ $employees->links() }}</div>@endif
</div>
@endsection
