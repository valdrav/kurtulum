@extends('layouts.app')

@section('content')
<div class="page-header d-print-none">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">@yield('settings-title', __('app.settings'))</h2>
            @hasSection('settings-desc')
            <div class="text-muted mt-1">@yield('settings-desc')</div>
            @endif
        </div>
        @yield('settings-actions')
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-3">
        <div class="ef-settings-nav-scroll">
            @include('partials.settings-nav')
        </div>
    </div>
    <div class="col-lg-9">
        @yield('settings-content')
    </div>
</div>
@endsection
