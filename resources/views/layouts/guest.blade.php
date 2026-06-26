<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($isRtl ?? false) ? 'rtl' : 'ltr' }}" data-bs-theme="{{ $currentTheme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', app_brand())</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.3.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="ef-guest">
    <main class="ef-guest-main">
        @if($errors->any())
        <div class="alert alert-danger ef-alert mx-auto" style="max-width:24rem">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
        @endif
        @yield('content')
    </main>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
    @stack('scripts')
</body>
</html>
