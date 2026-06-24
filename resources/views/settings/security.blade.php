@extends('layouts.settings')
@section('settings-title', __('settings.security'))

@section('settings-content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('settings.security.update') }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.session_lifetime') }}</label>
                    <input type="number" name="session_lifetime" class="form-control" value="{{ old('session_lifetime', $settings['session_lifetime']) }}" min="15" max="1440">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.password_min_length') }}</label>
                    <input type="number" name="password_min_length" class="form-control" value="{{ old('password_min_length', $settings['password_min_length']) }}" min="6" max="32">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.login_attempts') }}</label>
                    <input type="number" name="login_attempts" class="form-control" value="{{ old('login_attempts', $settings['login_attempts']) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.lockout_minutes') }}</label>
                    <input type="number" name="lockout_minutes" class="form-control" value="{{ old('lockout_minutes', $settings['lockout_minutes']) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.audit_retention_days') }}</label>
                    <input type="number" name="audit_retention_days" class="form-control" value="{{ old('audit_retention_days', $settings['audit_retention_days']) }}">
                </div>
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <label class="form-check">
                        <input type="checkbox" name="require_strong_password" class="form-check-input" value="1" @checked(old('require_strong_password', $settings['require_strong_password']) == '1')>
                        <span class="form-check-label">{{ __('settings.require_strong_password') }}</span>
                    </label>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-check">
                        <input type="checkbox" name="force_single_session" class="form-check-input" value="1" @checked(old('force_single_session', $settings['force_single_session']) == '1')>
                        <span class="form-check-label">{{ __('settings.force_single_session') }}</span>
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
        </form>
    </div>
</div>
@endsection
