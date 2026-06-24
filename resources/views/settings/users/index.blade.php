@extends('layouts.settings')
@section('settings-title', __('settings.users'))
@section('settings-desc', __('settings.users_desc'))

@section('settings-actions')
@if(can_access('users.create'))
<div class="col-auto">
    <a href="{{ route('settings.users.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ __('settings.add_user') }}</a>
</div>
@endif
@endsection

@section('settings-content')
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="{{ __('app.search') }}..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="role" class="form-select">
                    <option value="">{{ __('app.all') }} {{ __('settings.user_role') }}</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ role_label($role->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-primary w-100">{{ __('app.filter') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter table-modern card-table">
            <thead>
                <tr>
                    <th>{{ __('settings.users') }}</th>
                    <th>{{ __('settings.user_role') }}</th>
                    <th>{{ __('settings.departments') }}</th>
                    <th>{{ __('app.status') }}</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-sm bg-primary-lt me-2">{{ substr($user->name, 0, 1) }}</span>
                            <div>
                                <div class="fw-semibold">{{ $user->name }}</div>
                                <div class="text-muted small">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @foreach($user->roles as $role)
                        <span class="badge bg-azure-lt">{{ role_label($role->name) }}</span>
                        @endforeach
                    </td>
                    <td>{{ $user->department?->name ?? '—' }}</td>
                    <td>
                        @if($user->is_active)
                        <span class="badge badge-status-active">{{ __('settings.active') }}</span>
                        @else
                        <span class="badge badge-status-inactive">{{ __('settings.inactive') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-list flex-nowrap">
                            @if(can_access('users.edit'))
                            <a href="{{ route('settings.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">{{ __('app.edit') }}</a>
                            @endif
                            @if(can_access('users.delete') && $user->id !== auth()->id())
                            <form action="{{ route('settings.users.destroy', $user) }}" method="POST" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">{{ __('app.delete') }}</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer">{{ $users->links() }}</div>
    @endif
</div>
@endsection
