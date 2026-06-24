@extends('layouts.settings')
@section('settings-title', __('settings.edit_role') . ': ' . role_label($role->name))

@section('settings-content')
<form method="POST" action="{{ route('settings.roles.update', $role) }}">
    @csrf @method('PUT')

    @if($role->name === 'super-admin')
    <div class="alert alert-info">{{ __('settings.super_admin_locked') }}</div>
    @else
    @foreach($permissions as $module => $modulePermissions)
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">{{ permission_module_label($module) }}</h3>
        </div>
        <div class="card-body permission-grid">
            <div class="row">
                @foreach($modulePermissions as $permission)
                @php($action = explode('.', $permission->name)[1] ?? $permission->name)
                <div class="col-md-6 col-lg-4">
                    <label class="form-check">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="form-check-input"
                            @checked(in_array($permission->name, old('permissions', $rolePermissions)))>
                        <span class="form-check-label">{{ permission_action_label($action) }}</span>
                    </label>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
        <a href="{{ route('settings.roles.index') }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a>
    </div>
    @endif
</form>
@endsection
