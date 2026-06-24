@php
$mobileNav = [
    ['route' => 'dashboard', 'icon' => 'ti-chart-dots-3', 'label' => 'Panel', 'perm' => 'dashboard.view'],
    ['route' => 'finance.index', 'icon' => 'ti-wallet', 'label' => 'Finans', 'perm' => 'finance.view'],
    ['route' => 'orders.index', 'icon' => 'ti-shopping-cart', 'label' => 'Sipariş', 'perm' => 'orders.view'],
    ['route' => 'tasks.index', 'icon' => 'ti-checklist', 'label' => 'Görev', 'perm' => 'tasks.view'],
    ['route' => 'shipments.index', 'icon' => 'ti-truck-delivery', 'label' => 'Sevkiyat', 'perm' => 'shipments.view'],
];
@endphp

<nav class="ef-bottom-nav d-lg-none" aria-label="Mobil navigasyon">
    @foreach($mobileNav as $item)
    @if(can_access($item['perm']))
    <a href="{{ route($item['route']) }}"
       class="ef-bottom-nav-item {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']) ? 'active' : '' }}">
        <i class="ti {{ $item['icon'] }}"></i>
        <span>{{ $item['label'] }}</span>
    </a>
    @endif
    @endforeach
    <button type="button" class="ef-bottom-nav-item" data-bs-toggle="offcanvas" data-bs-target="#mobileMoreMenu">
        <i class="ti ti-dots"></i>
        <span>Daha</span>
    </button>
</nav>

<div class="offcanvas offcanvas-bottom ef-more-sheet" tabindex="-1" id="mobileMoreMenu">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menü</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="row g-2">
            @php
            $moreItems = [
                ['route' => 'tasks.index', 'icon' => 'ti-checklist', 'label' => __('app.tasks'), 'perm' => 'tasks.view'],
                ['route' => 'documents.index', 'icon' => 'ti-files', 'label' => __('app.documents'), 'perm' => 'documents.view'],
                ['route' => 'customers.index', 'icon' => 'ti-users', 'label' => __('app.customers'), 'perm' => 'customers.view'],
                ['route' => 'suppliers.index', 'icon' => 'ti-building-factory', 'label' => __('app.suppliers'), 'perm' => 'suppliers.view'],
                ['route' => 'vessels.track.index', 'icon' => 'ti-ship', 'label' => __('logistics.vessel_tracking'), 'perm' => 'shipments.view'],
                ['route' => 'emails.index', 'icon' => 'ti-mail', 'label' => __('app.emails'), 'perm' => 'emails.view'],
                ['route' => 'reports.index', 'icon' => 'ti-chart-bar', 'label' => __('app.reports'), 'perm' => 'reports.view'],
                ['route' => 'settings.index', 'icon' => 'ti-settings', 'label' => __('app.settings'), 'perm' => 'settings.view'],
            ];
            @endphp
            @foreach($moreItems as $item)
            @if(can_access($item['perm']))
            <div class="col-4">
                <a href="{{ route($item['route']) }}" class="ef-more-tile">
                    <i class="ti {{ $item['icon'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</div>
