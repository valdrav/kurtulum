@if(can_access('customers.edit'))
@php $portal = $customer->portalAccess; @endphp
<div class="card mb-3 border-primary border-opacity-25">
    <div class="card-header">
        <h3 class="card-title mb-0"><i class="ti ti-lock-access me-1"></i>{{ __('portal.access_title') }}</h3>
    </div>
    <div class="card-body">
        <p class="text-muted small">{{ __('portal.security_note') }}</p>

        <form method="POST" action="{{ $portal ? route('customers.portal.update', $customer) : route('customers.portal.store', $customer) }}">
            @csrf
            @if($portal) @method('PUT') @endif

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="portal-active" @checked(old('is_active', $portal?->is_active))>
                <label class="form-check-label" for="portal-active">{{ __('portal.access_active') }}</label>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('portal.portal_email') }}</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $portal?->user?->email ?? $customer->email) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('portal.portal_password') }}</label>
                    <input type="password" name="password" class="form-control" @if(! $portal) required @endif>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('auth.password_confirmation') ?? 'Şifre tekrar' }}</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>
            </div>
            @if($portal)<p class="small text-muted">{{ __('portal.portal_password_hint') }}</p>@endif

            <div class="mb-2 fw-semibold">{{ __('portal.permissions') }}</div>
            <div class="row g-2 mb-3">
                @foreach([
                    'view_orders' => 'perm_orders',
                    'view_shipments' => 'perm_shipments',
                    'view_shipment_costs' => 'perm_shipment_costs',
                    'view_directory' => 'perm_directory',
                    'edit_profile' => 'perm_edit_profile',
                ] as $field => $label)
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="portal-{{ $field }}" @checked(old($field, $portal?->{$field} ?? ($field !== 'view_directory')))>
                        <label class="form-check-label" for="portal-{{ $field }}">{{ __('portal.'.$label) }}</label>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('app.save') }}</button>
                @if($portal?->is_active)
                <a href="{{ url('/portal') }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="ti ti-external-link me-1"></i>Portal</a>
                @endif
            </div>
        </form>

        @if($portal)
        <form method="POST" action="{{ route('customers.portal.destroy', $customer) }}" class="mt-3" data-confirm="{{ __('portal.access_revoked') }}">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">{{ __('portal.access_revoked') }}</button>
        </form>
        @endif
    </div>
</div>
@endif
