<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('install.welcome_title') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.3.0/dist/tabler-icons.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column bg-primary">
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <a href="#" class="navbar-brand navbar-brand-autodark">
                    <i class="ti ti-building-warehouse text-white" style="font-size:2rem"></i>
                    <span class="text-white fs-2 fw-bold ms-2">ExportFlow ERP</span>
                </a>
            </div>
            <div class="card card-md">
                <div class="card-body">
                    @yield('content')
                </div>
            </div>
            <div class="text-center text-white mt-3 opacity-75">
                <small>v{{ config('ticari.version') }} &mdash; Laravel {{ app()->version() }}</small>
            </div>
        </div>
    </div>
</body>
</html>
