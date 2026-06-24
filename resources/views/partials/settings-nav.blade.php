<div class="ef-settings-nav-wrap">
<div class="card sticky-top" style="top: 1rem">
    <div class="card-body p-2">
        <div class="list-group list-group-flush settings-nav">
            <a href="{{ route('settings.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.index') ? 'active' : '' }}">
                <i class="ti ti-layout-dashboard me-2"></i>{{ __('settings.overview') }}
            </a>

            <div class="list-group-item text-muted text-uppercase small fw-bold border-0 pt-3 pb-1">{{ __('settings.organization') }}</div>
            @if(can_access('settings.view'))
            <a href="{{ route('settings.company') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.company*') ? 'active' : '' }}">
                <i class="ti ti-building me-2"></i>{{ __('settings.company') }}
            </a>
            <a href="{{ route('settings.branding') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.branding*') ? 'active' : '' }}">
                <i class="ti ti-palette me-2"></i>{{ __('settings.branding') }}
            </a>
            @endif
            @if(can_access('settings.edit'))
            <a href="{{ route('settings.departments.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.departments.*') ? 'active' : '' }}">
                <i class="ti ti-sitemap me-2"></i>{{ __('settings.departments') }}
            </a>
            @endif

            <div class="list-group-item text-muted text-uppercase small fw-bold border-0 pt-3 pb-1">{{ __('settings.access_control') }}</div>
            @if(can_access('users.view'))
            <a href="{{ route('settings.users.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.users.*') ? 'active' : '' }}">
                <i class="ti ti-users me-2"></i>{{ __('settings.users') }}
            </a>
            @endif
            @if(can_access('settings.edit'))
            <a href="{{ route('settings.roles.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.roles.*') ? 'active' : '' }}">
                <i class="ti ti-shield-lock me-2"></i>{{ __('settings.roles') }}
            </a>
            @endif
            @if(can_access('settings.edit'))
            <a href="{{ route('settings.security') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.security*') ? 'active' : '' }}">
                <i class="ti ti-lock me-2"></i>{{ __('settings.security') }}
            </a>
            @endif

            <div class="list-group-item text-muted text-uppercase small fw-bold border-0 pt-3 pb-1">{{ __('settings.localization') }}</div>
            <a href="{{ route('settings.languages.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.languages.*') ? 'active' : '' }}">
                <i class="ti ti-language me-2"></i>{{ __('extensions.languages') }}
            </a>
            <a href="{{ route('settings.currencies.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.currencies.*') ? 'active' : '' }}">
                <i class="ti ti-currency-dollar me-2"></i>{{ __('extensions.currencies') }}
            </a>
            <a href="{{ route('settings.lookups.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.lookups.*') ? 'active' : '' }}">
                <i class="ti ti-list-details me-2"></i>{{ __('extensions.lookups') }}
            </a>

            <div class="list-group-item text-muted text-uppercase small fw-bold border-0 pt-3 pb-1">{{ __('settings.integrations') }}</div>
            <a href="{{ route('settings.payment-methods.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.payment-methods.*') ? 'active' : '' }}">
                <i class="ti ti-credit-card me-2"></i>{{ __('extensions.payment_methods') }}
            </a>
            <a href="{{ route('settings.mail') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.mail*') ? 'active' : '' }}">
                <i class="ti ti-mail-cog me-2"></i>{{ __('settings.mail') }}
            </a>
            <a href="{{ route('settings.ai') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.ai*') ? 'active' : '' }}">
                <i class="ti ti-sparkles me-2"></i>{{ __('settings.ai') }}
            </a>
            <a href="{{ route('settings.marinetraffic') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.marinetraffic*') ? 'active' : '' }}">
                <i class="ti ti-ship me-2"></i>Gemi Takibi API
            </a>

            <div class="list-group-item text-muted text-uppercase small fw-bold border-0 pt-3 pb-1">{{ __('settings.system') }}</div>
            <a href="{{ route('settings.modules.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.modules.*') ? 'active' : '' }}">
                <i class="ti ti-puzzle me-2"></i>{{ __('extensions.modules') }}
            </a>
            <a href="{{ route('settings.audit-log') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.audit-log') ? 'active' : '' }}">
                <i class="ti ti-history me-2"></i>{{ __('app.audit_log') }}
            </a>
            <a href="{{ route('settings.updates') }}" class="list-group-item list-group-item-action {{ request()->routeIs('settings.updates*') ? 'active' : '' }}">
                <i class="ti ti-download me-2"></i>{{ __('app.updates') }}
            </a>
        </div>
    </div>
</div>
</div>
