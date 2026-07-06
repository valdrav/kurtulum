@extends('layouts.app')
@section('title', __('directory.import'))

@section('content')
@include('partials.page-header', ['title' => __('directory.import'), 'subtitle' => __('directory.import_hint')])

<form method="POST" action="{{ route('directory.import.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">CSV Dosyası</label>
                <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
            </div>
            <p class="text-muted small mb-0">Örnek başlık satırı: <code>Ad;Soyad;Telefon;Açıklama</code></p>
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('directory.import') }}</button>
            <a href="{{ route('directory.index') }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a>
        </div>
    </div>
</form>
@endsection
