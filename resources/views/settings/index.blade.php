@extends('layouts.settings')
@section('settings-title', __('app.settings'))
@section('settings-desc', __('settings.overview'))

@section('settings-content')
<div class="row row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">{{ __('settings.stat_users') }}</div>
            <div class="h1 mb-0">{{ $stats['users'] }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">{{ __('settings.stat_employees') }}</div>
            <div class="h1 mb-0">{{ $stats['employees'] }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">{{ __('settings.stat_languages') }}</div>
            <div class="h1 mb-0">{{ $stats['languages'] }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">{{ __('settings.stat_modules') }}</div>
            <div class="h1 mb-0">{{ $stats['modules'] }}</div>
        </div></div>
    </div>
</div>

<div class="row row-cards">
    @foreach([
        ['route' => 'settings.company', 'icon' => 'ti-building', 'color' => 'blue', 'title' => 'settings.company', 'desc' => 'settings.company_desc'],
        ['route' => 'settings.branding', 'icon' => 'ti-palette', 'color' => 'indigo', 'title' => 'settings.branding', 'desc' => 'settings.branding_desc'],
        ['route' => 'settings.users.index', 'icon' => 'ti-users', 'color' => 'green', 'title' => 'settings.users', 'desc' => 'settings.users_desc', 'perm' => 'users.view'],
        ['route' => 'settings.roles.index', 'icon' => 'ti-shield-lock', 'color' => 'red', 'title' => 'settings.roles', 'desc' => 'settings.roles_desc', 'perm' => 'settings.edit'],
        ['route' => 'settings.departments.index', 'icon' => 'ti-sitemap', 'color' => 'purple', 'title' => 'settings.departments', 'desc' => 'settings.departments_desc', 'perm' => 'settings.edit'],
        ['route' => 'settings.security', 'icon' => 'ti-lock', 'color' => 'orange', 'title' => 'settings.security', 'desc' => 'settings.security_desc'],
        ['route' => 'settings.languages.index', 'icon' => 'ti-language', 'color' => 'cyan', 'title' => 'extensions.languages', 'desc' => 'extensions.languages_desc'],
        ['route' => 'settings.payment-methods.index', 'icon' => 'ti-credit-card', 'color' => 'yellow', 'title' => 'extensions.payment_methods', 'desc' => 'extensions.payment_methods_desc'],
        ['route' => 'settings.modules.index', 'icon' => 'ti-puzzle', 'color' => 'pink', 'title' => 'extensions.modules', 'desc' => 'extensions.modules_desc'],
    ] as $item)
    @if(empty($item['perm']) || can_access($item['perm']))
    <div class="col-md-6 col-xl-4">
        <a href="{{ route($item['route']) }}" class="card card-link card-link-pop h-100 text-decoration-none">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <span class="avatar bg-{{ $item['color'] }}-lt me-3"><i class="ti {{ $item['icon'] }}"></i></span>
                    <div>
                        <h3 class="mb-1">{{ __($item['title']) }}</h3>
                        <div class="text-muted small">{{ __($item['desc']) }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endif
    @endforeach
</div>
@endsection
