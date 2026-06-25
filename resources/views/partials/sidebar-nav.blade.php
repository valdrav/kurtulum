@php
$nav = [
    ['route' => 'dashboard', 'icon' => 'ti-dashboard', 'label' => 'app.dashboard', 'perm' => 'dashboard.view'],
    ['route' => 'customers.index', 'icon' => 'ti-users', 'label' => 'app.customers', 'perm' => 'customers.view'],
    ['route' => 'suppliers.index', 'icon' => 'ti-building-factory', 'label' => 'app.suppliers', 'perm' => 'suppliers.view'],
    ['route' => 'orders.index', 'icon' => 'ti-shopping-cart', 'label' => 'app.orders', 'perm' => 'orders.view'],
    ['route' => 'shipments.index', 'icon' => 'ti-truck-delivery', 'label' => 'app.shipments', 'perm' => 'shipments.view'],
    ['route' => 'shipments.costs.index', 'icon' => 'ti-receipt', 'label' => 'logistics.shipment_costs', 'perm' => 'shipments.view'],
    ['route' => 'vessels.track.index', 'icon' => 'ti-ship', 'label' => 'logistics.vessel_tracking', 'perm' => 'shipments.view'],
    ['route' => 'finance.index', 'icon' => 'ti-currency-lira', 'label' => 'app.finance', 'perm' => 'finance.view'],
    ['route' => 'documents.index', 'icon' => 'ti-files', 'label' => 'app.documents', 'perm' => 'documents.view'],
    ['route' => 'tasks.index', 'icon' => 'ti-checklist', 'label' => 'app.tasks', 'perm' => 'tasks.view'],
    ['route' => 'employees.index', 'icon' => 'ti-id-badge', 'label' => 'app.employees', 'perm' => 'employees.view'],
    ['route' => 'reports.index', 'icon' => 'ti-chart-bar', 'label' => 'app.reports', 'perm' => 'reports.view'],
    ['route' => 'emails.index', 'icon' => 'ti-mail', 'label' => 'app.emails', 'perm' => 'emails.view'],
    ['route' => 'documents.tools.index', 'icon' => 'ti-file-settings', 'label' => 'documents.tools.title', 'perm' => 'documents.view'],
    ['route' => 'ai.index', 'icon' => 'ti-sparkles', 'label' => 'app.ai_assistant', 'perm' => 'ai.view'],
    ['route' => 'settings.index', 'icon' => 'ti-settings', 'label' => 'app.settings', 'perm' => 'settings.view'],
];
@endphp

<ul class="navbar-nav">
    @foreach($nav as $item)
    @if(can_access($item['perm']))
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
            <span class="nav-link-icon"><i class="ti {{ $item['icon'] }}"></i></span>
            <span class="nav-link-title">{{ __($item['label']) }}</span>
        </a>
    </li>
    @endif
    @endforeach
    @foreach($moduleMenuItems ?? [] as $menuItem)
    @if(Route::has($menuItem['route'] ?? ''))
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs(($menuItem['route'] ?? '').'*') ? 'active' : '' }}" href="{{ route($menuItem['route']) }}">
            <span class="nav-link-icon"><i class="ti {{ $menuItem['icon'] ?? 'ti-puzzle' }}"></i></span>
            <span class="nav-link-title">{{ $menuItem['title'] }}</span>
        </a>
    </li>
    @endif
    @endforeach
</ul>
