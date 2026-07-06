@php
    $access = portal()->access;
    $nav = [];
    if ($access->view_orders) $nav[] = ['route' => 'portal.orders.index', 'icon' => 'ti-shopping-cart', 'label' => __('portal.my_orders')];
    if ($access->view_shipments) $nav[] = ['route' => 'portal.shipments.index', 'icon' => 'ti-truck-delivery', 'label' => __('portal.my_shipments')];
    if ($access->view_directory) $nav[] = ['route' => 'portal.directory.index', 'icon' => 'ti-address-book', 'label' => __('portal.directory')];
    if ($access->edit_profile) $nav[] = ['route' => 'portal.profile.edit', 'icon' => 'ti-user', 'label' => __('portal.my_profile')];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ $currentTheme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('portal.title')) — {{ portal()->customer()->company_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.3.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <style>
        :root { --ef-primary: {{ site_branding()->themeColor() }}; --ef-sidebar-w: 14rem; }
        .ef-portal-shell { min-height: 100dvh; display: flex; }
        .ef-portal-sidebar { width: var(--ef-sidebar-w); background: linear-gradient(180deg, #1e3a5f 0%, #0f172a 100%); color: #e2e8f0; padding: 1rem 0; flex-shrink: 0; }
        .ef-portal-sidebar .nav-link { color: rgba(255,255,255,.85); padding: .65rem 1rem; display: flex; gap: .5rem; align-items: center; }
        .ef-portal-sidebar .nav-link.active, .ef-portal-sidebar .nav-link:hover { background: rgba(255,255,255,.1); color: #fff; }
        .ef-portal-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .ef-portal-topbar { height: 3.5rem; border-bottom: 1px solid var(--tblr-border-color); display: flex; align-items: center; justify-content: space-between; padding: 0 1rem; background: var(--tblr-body-bg); }
        .ef-portal-content { padding: 1rem; flex: 1; }
        @media (max-width: 991.98px) {
            .ef-portal-sidebar { display: none; }
            .ef-portal-mobile-nav { display: flex; gap: .35rem; overflow-x: auto; padding: .5rem; border-bottom: 1px solid var(--tblr-border-color); }
        }
        @media (min-width: 992px) { .ef-portal-mobile-nav { display: none; } }
    </style>
    @stack('styles')
</head>
<body class="ef-app">
<div class="ef-portal-shell">
    <aside class="ef-portal-sidebar d-none d-lg-block">
        <div class="px-3 mb-3">
            <div class="fw-bold">{{ portal()->customer()->company_name }}</div>
            <div class="small opacity-75">{{ __('portal.title') }}</div>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}" href="{{ route('portal.dashboard') }}"><i class="ti ti-dashboard"></i> {{ __('portal.dashboard') }}</a>
            @foreach($nav as $item)
            <a class="nav-link {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}"><i class="ti {{ $item['icon'] }}"></i> {{ $item['label'] }}</a>
            @endforeach
        </nav>
    </aside>
    <div class="ef-portal-main">
        <header class="ef-portal-topbar">
            <div class="fw-semibold">@yield('title', __('portal.dashboard'))</div>
            <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="btn btn-sm btn-outline-secondary"><i class="ti ti-logout me-1"></i>{{ __('app.logout') }}</button></form>
        </header>
        <nav class="ef-portal-mobile-nav d-lg-none">
            <a class="btn btn-sm btn-ghost-secondary" href="{{ route('portal.dashboard') }}">{{ __('portal.dashboard') }}</a>
            @foreach($nav as $item)
            <a class="btn btn-sm btn-ghost-secondary" href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>
        <main class="ef-portal-content ef-container">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
@stack('scripts')
</body>
</html>
