@extends('layouts.install')

@section('content')
<h2 class="h2 mb-2">{{ __('install.admin') }}</h2>
<p class="text-muted mb-4">{{ __('install.admin_desc') }}</p>
<form method="POST" action="{{ route('install.admin.store') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">Ad Soyad</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Şifre</label>
            <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Şifre Tekrar</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Dil</label>
        <select name="locale" class="form-select">
            @foreach(config('ticari.locales') as $code => $loc)
            <option value="{{ $code }}" @selected(old('locale', 'tr') === $code)>{{ $loc['name'] }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-primary w-100">
        <i class="ti ti-check me-1"></i> Kurulumu Tamamla
    </button>
</form>
@endsection
