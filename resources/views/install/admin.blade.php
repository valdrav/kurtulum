@extends('layouts.install')

@section('content')
<h2 class="h2 mb-2">{{ __('install.admin') }}</h2>
<p class="text-muted mb-4">{{ __('install.admin_desc') }}</p>
<form method="POST" action="{{ route('install.admin.store') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">Ad Soyad</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Şifre</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Şifre Tekrar</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Dil</label>
        <select name="locale" class="form-select @error('locale') is-invalid @enderror">
            @foreach(['tr', 'en', 'ar'] as $code)
            @if(isset(config('ticari.locales')[$code]))
            <option value="{{ $code }}" @selected(old('locale', 'tr') === $code)>{{ config('ticari.locales')[$code]['name'] }}</option>
            @endif
            @endforeach
        </select>
        @error('locale')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary w-100" id="install-admin-submit">
        <i class="ti ti-check me-1"></i> Kurulumu Tamamla
    </button>
</form>
<script>
document.getElementById('install-admin-submit')?.closest('form')?.addEventListener('submit', function () {
    const btn = document.getElementById('install-admin-submit');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Kuruluyor…';
    }
});
</script>
@endsection
