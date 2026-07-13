<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($isRtl ?? false) ? 'rtl' : 'ltr' }}" data-bs-theme="{{ $currentTheme ?? 'light' }}" class="ef-app-root">
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
    @include('partials.tabler-icons')
    <link href="{{ asset('css/theme.css') }}?v={{ is_file($themeCssPath = public_path('css/theme.css')) ? filemtime($themeCssPath) : 1 }}" rel="stylesheet">
    <style>
        :root {
            --ef-primary: {{ site_branding()->themeColor() }};
            --ef-primary-dark: {{ site_branding()->themeColorDark() }};
            --ef-primary-rgb: {{ site_branding()->themeColorRgb() }};
            @auth
            @foreach(navbar()->cssVariables() as $var => $value)
            {{ $var }}: {{ $value }};
            @endforeach
            @endauth
        }
        @auth
        .ef-sidebar {
            background: {{ navbar()->sidebarBackground() }};
            color: var(--ef-sidebar-text, #e2e8f0);
        }
        .ef-sidebar-nav .nav-link {
            color: color-mix(in srgb, var(--ef-sidebar-text, #e2e8f0) 85%, transparent);
        }
        .ef-sidebar-nav .nav-link.active {
            background: rgba(var(--ef-sidebar-active-rgb, var(--ef-primary-rgb)), 0.35);
        }
        @endauth
    </style>
    @if(($isRtl ?? false))
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.rtl.min.css" rel="stylesheet">
    @endif
    <style>[x-cloak]{display:none!important}</style>
    @stack('styles')
</head>
<body class="ef-app @auth ef-app--auth @endauth" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    @auth
    <div class="ef-sidebar-backdrop" :class="{ 'show': sidebarOpen }" @click="sidebarOpen = false"></div>

    <aside class="ef-sidebar" :class="{ 'open': sidebarOpen }">
        <div class="ef-sidebar-head">
            <a href="{{ route('dashboard') }}" class="ef-brand">
                @include('partials.site-logo')
                @if(navbar()->showSidebarFlag('show_brand_text'))
                <span class="ef-brand-text">{{ app_brand() }}</span>
                @endif
            </a>
            <button type="button" class="ef-sidebar-close d-lg-none" @click="sidebarOpen = false" aria-label="Kapat">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <nav class="ef-sidebar-nav">
            @include('partials.sidebar-nav')
        </nav>
        @if(navbar()->showSidebarFlag('show_user_footer'))
        <div class="ef-sidebar-foot d-none d-lg-block">
            <div class="ef-user-mini">
                @include('partials.user-avatar', ['user' => auth()->user(), 'size' => 'sm'])
                <div class="min-w-0">
                    <div class="fw-semibold small text-truncate">{{ auth()->user()->name }}</div>
                    <div class="text-muted small text-truncate">{{ auth()->user()->email }}</div>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-light w-100 mt-2">
                <i class="ti ti-user me-1"></i>{{ __('app.profile') }}
            </a>
            <form action="{{ route('logout') }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-light w-100">
                    <i class="ti ti-logout me-1"></i>{{ __('app.logout') }}
                </button>
            </form>
        </div>
        @endif
        <div class="ef-sidebar-foot d-lg-none border-top border-white border-opacity-10 p-3">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-light w-100">
                    <i class="ti ti-logout me-1"></i>{{ __('app.logout') }}
                </button>
            </form>
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
                @if(navbar()->showTopbarFlag('show_brand_desktop'))
                <div class="ef-topbar-brand d-none d-lg-block">{{ app_brand() }}</div>
                @endif
                <div class="ef-topbar-title d-lg-none">@yield('title', app_brand())</div>
            </div>
            <div class="ef-topbar-end">
                @if(navbar()->showTopbarFlag('show_locale'))
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
                @endif
                @if(navbar()->showTopbarFlag('show_theme_toggle'))
                <a href="{{ route('theme.switch', ($currentTheme ?? 'light') === 'light' ? 'dark' : 'light') }}" class="ef-icon-btn" title="{{ __('app.dark_theme') }}">
                    <i class="ti ti-{{ ($currentTheme ?? 'light') === 'light' ? 'moon' : 'sun' }}"></i>
                </a>
                @endif
                <div class="dropdown">
                    <button class="ef-avatar-btn" data-bs-toggle="dropdown" aria-label="{{ __('app.profile') }}">
                        @include('partials.user-avatar', ['user' => auth()->user(), 'size' => 'sm'])
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="ti ti-user me-2"></i>{{ __('app.profile') }}</a>
                        @if(navbar()->showTopbarFlag('show_profile_menu'))
                        <a class="dropdown-item" href="{{ route('emails.accounts') }}"><i class="ti ti-mail me-2"></i>E-posta Hesaplarım</a>
                        @endif
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
            @if(navbar()->showCurrencyBar())
            @include('partials.currency-bar')
            @endif
            @include('partials.mobile-nav')
        </div>
        @endauth
    </div>

    @auth
    @include('partials.confirm-modal')
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
    @stack('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    @auth
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {});
        }
    </script>
    @endauth
</body>
</html>
