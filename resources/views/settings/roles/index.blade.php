@extends('layouts.settings')
@section('settings-title', __('settings.roles'))

@section('settings-content')
<div class="row row-cards">
    @foreach($roles as $role)
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h3 class="card-title mb-1">{{ role_label($role->name) }}</h3>
                        <div class="text-muted small">{{ __('settings.role_code') }}: {{ $role->name }}</div>
                    </div>
                    @if($role->name === 'super-admin')
                    <span class="badge bg-red-lt">{{ __('settings.system_role') }}</span>
                    @endif
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="subheader">{{ __('settings.permissions_count') }}</div>
                        <div class="h3 mb-0">{{ $role->permissions_count }}</div>
                    </div>
                    <div class="col-6">
                        <div class="subheader">{{ __('settings.users_count') }}</div>
                        <div class="h3 mb-0">{{ $role->users_count ?? 0 }}</div>
                    </div>
                </div>
                @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                <a href="{{ route('settings.roles.edit', $role) }}" class="btn btn-outline-primary w-100">{{ __('settings.edit_role') }}</a>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
