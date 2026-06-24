@extends('layouts.app')

@section('title', __('app.login'))

@section('content')
<div class="container-tight py-4" style="max-width:24rem;margin:4rem auto">
    <div class="text-center mb-4 ef-login-brand">
        @if(site_branding()->hasLogo())
        <img src="{{ site_branding()->logoUrl() }}" alt="{{ app_brand() }}" class="ef-login-logo mb-3">
        @else
        <i class="ti ti-building-warehouse text-primary" style="font-size:3rem"></i>
        @endif
        <h1 class="mt-2">{{ app_brand() }}</h1>
        @if(site_branding()->tagline())
        <p class="text-muted mb-0">{{ site_branding()->tagline() }}</p>
        @endif
        <p class="text-muted mt-2">{{ __('app.login') }}</p>
    </div>
    <div class="card card-md">
        <div class="card-body">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">E-posta</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Şifre</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" value="1" checked>
                        <span class="form-check-label">Beni hatırla (oturum açık kalsın)</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary w-100">{{ __('app.login') }}</button>
            </form>
        </div>
    </div>
    @if(site_branding()->footerText())
    <p class="text-center text-muted small mt-4 mb-0">{{ site_branding()->footerText() }}</p>
    @endif
</div>
@endsection
