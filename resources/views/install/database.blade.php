@extends('layouts.install')

@section('content')
<h2 class="h2 mb-2">{{ __('install.database') }}</h2>
<p class="text-muted mb-4">{{ __('install.database_desc') }}</p>

@if($errors->has('db_connection'))
<div class="alert alert-danger">{{ $errors->first('db_connection') }}</div>
@endif

<form method="POST" action="{{ route('install.database.store') }}" id="install-db-form">
    @csrf
    <div class="mb-3">
        <label class="form-label">{{ __('install.app_name') }}</label>
        <input type="text" name="app_name" class="form-control" value="{{ old('app_name', 'ExportFlow ERP') }}" required>
    </div>
    <div class="mb-4">
        <label class="form-label">{{ __('install.app_url') }}</label>
        <input type="url" name="app_url" class="form-control" value="{{ old('app_url', url('/')) }}" required>
    </div>

    <label class="form-label">{{ __('install.db_driver') }}</label>
    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <label class="form-selectgroup-item flex-fill">
                <input type="radio" name="db_driver" value="sqlite" class="form-selectgroup-input" {{ old('db_driver', 'sqlite') === 'sqlite' ? 'checked' : '' }}>
                <span class="form-selectgroup-label d-flex align-items-center p-3">
                    <span class="me-3"><i class="ti ti-file-database text-primary"></i></span>
                    <span>
                        <strong>{{ __('install.sqlite') }}</strong>
                        <span class="d-block text-muted small">{{ __('install.sqlite_hint') }}</span>
                    </span>
                </span>
            </label>
        </div>
        <div class="col-md-6">
            <label class="form-selectgroup-item flex-fill">
                <input type="radio" name="db_driver" value="mysql" class="form-selectgroup-input" {{ old('db_driver') === 'mysql' ? 'checked' : '' }}>
                <span class="form-selectgroup-label d-flex align-items-center p-3">
                    <span class="me-3"><i class="ti ti-database text-azure"></i></span>
                    <span>
                        <strong>{{ __('install.mysql') }}</strong>
                        <span class="d-block text-muted small">{{ __('install.mysql_hint') }}</span>
                    </span>
                </span>
            </label>
        </div>
    </div>

    <div id="sqlite-info" class="alert alert-info mb-3">
        <i class="ti ti-info-circle me-1"></i>
        {{ __('install.sqlite_auto') }}
    </div>

    <div id="mysql-fields" style="display:none">
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">{{ __('install.db_host') }}</label>
                <input type="text" name="db_host" class="form-control" value="{{ old('db_host', '127.0.0.1') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">{{ __('install.db_port') }}</label>
                <input type="text" name="db_port" class="form-control" value="{{ old('db_port', '3306') }}">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('install.db_database') }}</label>
            <input type="text" name="db_database" class="form-control" value="{{ old('db_database', 'exportflow') }}" placeholder="exportflow">
            <small class="text-muted">{{ __('install.db_database_hint') }}</small>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ __('install.db_username') }}</label>
                <input type="text" name="db_username" class="form-control" value="{{ old('db_username', 'root') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ __('install.db_password') }}</label>
                <input type="password" name="db_password" class="form-control" value="{{ old('db_password') }}">
                <small class="text-muted">{{ __('install.db_password_hint') }}</small>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="{{ route('install.requirements') }}" class="btn btn-outline-secondary">{{ __('app.back') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('install.continue') }}</button>
    </div>
</form>

<script>
(function () {
    const form = document.getElementById('install-db-form');
    const mysqlFields = document.getElementById('mysql-fields');
    const sqliteInfo = document.getElementById('sqlite-info');
    const radios = form.querySelectorAll('input[name="db_driver"]');

    function toggleFields() {
        const isMysql = form.querySelector('input[name="db_driver"]:checked')?.value === 'mysql';
        mysqlFields.style.display = isMysql ? 'block' : 'none';
        sqliteInfo.style.display = isMysql ? 'none' : 'block';
        mysqlFields.querySelectorAll('input').forEach(function (el) {
            el.required = isMysql;
        });
    }

    radios.forEach(function (r) { r.addEventListener('change', toggleFields); });
    toggleFields();
})();
</script>
@endsection
