<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($isRtl ?? false) ? 'rtl' : 'ltr' }}" data-bs-theme="{{ $currentTheme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ site_branding()->themeColor() }}">
    <meta name="description" content="{{ site_branding()->metaDescription() }}">
    <link rel="manifest" href="{{ route('manifest') }}">
    <link rel="icon" href="{{ site_branding()->faviconUrl() }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ site_branding()->appleIconUrl() }}">
    <title>@yield('title', app_brand())</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.3.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <style>
        :root {
            --ef-primary: {{ site_branding()->themeColor() }};
            --ef-primary-dark: {{ site_branding()->themeColorDark() }};
            --ef-primary-rgb: {{ site_branding()->themeColorRgb() }};
        }
    </style>
    @if(($isRtl ?? false))
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.rtl.min.css" rel="stylesheet">
    @endif
    <style>[x-cloak]{display:none!important}</style>
    @stack('styles')
</head>
<body class="ef-app" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    @auth
    <div class="ef-sidebar-backdrop" :class="{ 'show': sidebarOpen }" @click="sidebarOpen = false"></div>

    <aside class="ef-sidebar" :class="{ 'open': sidebarOpen }">
        <div class="ef-sidebar-head">
            <a href="{{ route('dashboard') }}" class="ef-brand">
                @include('partials.site-logo')
                <span class="ef-brand-text">{{ app_brand() }}</span>
            </a>
            <button type="button" class="ef-sidebar-close d-lg-none" @click="sidebarOpen = false" aria-label="Kapat">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <nav class="ef-sidebar-nav">
            @include('partials.sidebar-nav')
        </nav>
        <div class="ef-sidebar-foot d-none d-lg-block">
            <div class="ef-user-mini">
                @include('partials.user-avatar', ['user' => auth()->user(), 'size' => 'sm'])
                <div>
                    <div class="fw-semibold small">{{ auth()->user()->name }}</div>
                    <div class="text-muted small">{{ auth()->user()->email }}</div>
                </div>
            </div>
        </div>
    </aside>
    @endauth

    <div class="ef-main @auth ef-main-auth @endauth">
        @auth
        @php
            $headerLanguages = $registryLanguages ?? registry()->languages();
            $currentLanguage = $headerLanguages->firstWhere('code', app()->getLocale()) ?? registry()->defaultLanguage();
        @endphp
        <header class="ef-topbar">
            <div class="ef-topbar-start">
                <button type="button" class="ef-icon-btn d-lg-none" @click="sidebarOpen = true" aria-label="Menü">
                    <i class="ti ti-menu-2"></i>
                </button>
                <div class="ef-topbar-brand d-none d-lg-block">{{ app_brand() }}</div>
                <div class="ef-topbar-title d-lg-none">@yield('title', app_brand())</div>
            </div>
            <div class="ef-topbar-end">
                <div class="dropdown ef-locale-dropdown">
                    <button class="ef-locale-btn" type="button" data-bs-toggle="dropdown" aria-label="{{ __('settings.profile_locale') }}">
                        <span class="ef-locale-flag" aria-hidden="true">{{ locale_flag_for($currentLanguage) }}</span>
                        <span class="ef-locale-label">{{ $currentLanguage?->native_name ?? $currentLanguage?->name ?? strtoupper(app()->getLocale()) }}</span>
                        <i class="ti ti-chevron-down ef-locale-chevron"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end ef-locale-menu">
                        @foreach($headerLanguages as $lang)
                        <a class="dropdown-item ef-locale-item @if(app()->getLocale() === $lang->code) active @endif" href="{{ route('locale.switch', $lang->code) }}">
                            <span class="ef-locale-item-flag">{{ locale_flag_for($lang) }}</span>
                            <span class="ef-locale-item-text">
                                <strong>{{ $lang->native_name ?? $lang->name }}</strong>
                                <small class="text-muted d-block">{{ strtoupper($lang->code) }}</small>
                            </span>
                            @if(app()->getLocale() === $lang->code)
                            <i class="ti ti-check ef-locale-check"></i>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>
                <a href="{{ route('theme.switch', ($currentTheme ?? 'light') === 'light' ? 'dark' : 'light') }}" class="ef-icon-btn" title="{{ __('app.dark_theme') }}">
                    <i class="ti ti-{{ ($currentTheme ?? 'light') === 'light' ? 'moon' : 'sun' }}"></i>
                </a>
                <div class="dropdown">
                    <button class="ef-avatar-btn" data-bs-toggle="dropdown">
                        @include('partials.user-avatar', ['user' => auth()->user(), 'size' => 'sm'])
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="ti ti-user me-2"></i>{{ __('app.profile') }}</a>
                        <a class="dropdown-item" href="{{ route('emails.accounts') }}"><i class="ti ti-mail me-2"></i>E-posta Hesaplarım</a>
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="dropdown-item text-danger"><i class="ti ti-logout me-2"></i>{{ __('app.logout') }}</button></form>
                    </div>
                </div>
            </div>
        </header>
        @endauth

        <main class="ef-content">
            <div class="ef-container">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible ef-alert" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif
                @if(session('warning'))
                <div class="alert alert-warning alert-dismissible ef-alert" role="alert">
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif
                @if($errors->any())
                <div class="alert alert-danger ef-alert">
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
                @endif
                @yield('content')
            </div>
        </main>

        @auth
        <div class="ef-dock">
            @include('partials.currency-bar')
            @include('partials.mobile-nav')
        </div>
        @endauth
    </div>

    @auth
    @include('partials.confirm-modal')
    @endauth

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
    @stack('scripts')
    @auth
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {});
        }
    </script>
    @endauth
</body>
</html>
