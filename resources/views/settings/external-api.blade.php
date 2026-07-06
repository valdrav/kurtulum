@extends('layouts.settings')
@section('settings-title', __('external_api.title'))

@section('settings-content')
@if(session('new_api_token'))
<div class="alert alert-warning border-warning">
    <h4 class="alert-title mb-2"><i class="ti ti-key me-1"></i>{{ __('external_api.token_show_once') }}</h4>
    <p class="mb-2">{{ __('external_api.token_for', ['name' => session('new_api_connection')]) }}</p>
    <div class="input-group mb-2">
        <input type="text" class="form-control font-monospace" id="new-api-token" value="{{ session('new_api_token') }}" readonly>
        <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('new-api-token').value)">{{ __('external_api.copy') }}</button>
    </div>
    <p class="text-muted small mb-0">{{ __('external_api.token_warning') }}</p>
</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('settings.external-api.update-global') }}">
            @csrf @method('PUT')
            <div class="d-flex align-items-start gap-3 mb-3">
                <span class="avatar bg-indigo-lt"><i class="ti ti-api"></i></span>
                <div class="flex-fill">
                    <h3 class="mb-1">{{ __('external_api.title') }}</h3>
                    <p class="text-muted small mb-0">{{ __('external_api.subtitle') }}</p>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-check form-switch">
                    <input type="checkbox" name="external_api_enabled" class="form-check-input" value="1" @checked(old('external_api_enabled', $settings['external_api_enabled']) == '1')>
                    <span class="form-check-label">{{ __('external_api.enabled') }}</span>
                </label>
            </div>
            <div class="bg-light rounded p-3 mb-3 small font-monospace">
                <div class="text-muted mb-1">{{ __('external_api.base_url') }}</div>
                <code>{{ $baseUrl }}</code>
            </div>
            <p class="text-muted small">{{ __('external_api.auth_hint') }}</p>
            @if(can_access('settings.edit'))
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
            @endif
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title mb-0">{{ __('external_api.endpoints') }}</h3></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Method</th><th>{{ __('external_api.endpoint') }}</th><th>{{ __('external_api.permission') }}</th></tr></thead>
            <tbody class="font-monospace small">
                <tr><td>GET</td><td>/me</td><td>—</td></tr>
                <tr><td>GET</td><td>/customer</td><td>{{ __('external_api.perm_customer') }}</td></tr>
                <tr><td>GET</td><td>/directory</td><td>{{ __('external_api.perm_directory') }}</td></tr>
                <tr><td>GET</td><td>/orders</td><td>{{ __('external_api.perm_orders') }}</td></tr>
                <tr><td>GET</td><td>/orders/{id}</td><td>{{ __('external_api.perm_orders') }}</td></tr>
                <tr><td>GET</td><td>/shipments</td><td>{{ __('external_api.perm_shipments') }}</td></tr>
                <tr><td>GET</td><td>/shipments/{id}</td><td>{{ __('external_api.perm_shipments') }}</td></tr>
            </tbody>
        </table>
    </div>
</div>

@if(can_access('settings.edit'))
<div class="card mb-3">
    <div class="card-header"><h3 class="card-title mb-0">{{ __('external_api.new_connection') }}</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('settings.external-api.store') }}">
            @csrf
            @include('settings._external-api-form', ['connection' => null])
            <button type="submit" class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ __('external_api.create_connection') }}</button>
        </form>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header"><h3 class="card-title mb-0">{{ __('external_api.connections') }}</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern mb-0">
            <thead>
                <tr>
                    <th>{{ __('external_api.connection_name') }}</th>
                    <th>{{ __('external_api.linked_customer') }}</th>
                    <th>{{ __('external_api.token') }}</th>
                    <th>{{ __('external_api.permissions_col') }}</th>
                    <th>{{ __('app.status') }}</th>
                    <th>{{ __('external_api.last_used') }}</th>
                    <th class="ef-table-actions"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($connections as $connection)
                <tr>
                    <td class="fw-medium">{{ $connection->name }}</td>
                    <td>{{ $connection->customer?->company_name ?? '—' }}</td>
                    <td><code>{{ $connection->token_prefix }}…</code></td>
                    <td class="small">
                        @foreach($connection->permissionsSummary() as $key => $allowed)
                            @if($allowed)
                            <span class="badge bg-blue-lt me-1">{{ __('external_api.perm_'.$key) }}</span>
                            @endif
                        @endforeach
                    </td>
                    <td>
                        @if($connection->is_active)
                        <span class="badge bg-green-lt">{{ __('settings.active') }}</span>
                        @else
                        <span class="badge bg-secondary-lt">{{ __('settings.inactive') }}</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ $connection->last_used_at?->format('d.m.Y H:i') ?? '—' }}</td>
                    <td class="ef-table-actions text-end">
                        @if(can_access('settings.edit'))
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-connection-{{ $connection->id }}"><i class="ti ti-edit"></i></button>
                        <form method="POST" action="{{ route('settings.external-api.regenerate', $connection) }}" class="d-inline" data-confirm="{{ __('external_api.regenerate_confirm') }}">@csrf<button class="btn btn-sm btn-outline-warning" title="{{ __('external_api.regenerate_token') }}"><i class="ti ti-refresh"></i></button></form>
                        <form method="POST" action="{{ route('settings.external-api.destroy', $connection) }}" class="d-inline" data-confirm="{{ __('app.confirm_delete') }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button></form>
                        @endif
                    </td>
                </tr>
                @if(can_access('settings.edit'))
                <tr class="collapse" id="edit-connection-{{ $connection->id }}">
                    <td colspan="7" class="bg-light">
                        <form method="POST" action="{{ route('settings.external-api.update', $connection) }}" class="p-2">
                            @csrf @method('PUT')
                            @include('settings._external-api-form', ['connection' => $connection])
                            <button type="submit" class="btn btn-sm btn-primary">{{ __('app.save') }}</button>
                        </form>
                    </td>
                </tr>
                @endif
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('external_api.no_connections') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
