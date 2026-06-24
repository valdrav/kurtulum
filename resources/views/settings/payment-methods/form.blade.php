@extends('layouts.app')
@section('title', $method->exists ? $method->name : __('extensions.add_payment_method'))
@section('content')
@include('partials.page-header', ['title' => __('extensions.payment_methods')])
<div class="card"><div class="card-body">
<form method="POST" action="{{ $method->exists ? route('settings.payment-methods.update', $method) : route('settings.payment-methods.store') }}">@csrf @if($method->exists)@method('PUT')@endif
<div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Kod *</label><input type="text" name="code" class="form-control" value="{{ old('code', $method->code) }}" required {{ $method->exists ? 'readonly' : '' }}></div>
    <div class="col-md-4 mb-3"><label class="form-label">Ad *</label><input type="text" name="name" class="form-control" value="{{ old('name', $method->name) }}" required></div>
    <div class="col-md-4 mb-3"><label class="form-label">Tip</label><select name="type" class="form-select"><option value="both">Both</option><option value="payment">Payment</option><option value="collection">Collection</option></select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Icon (Tabler)</label><input type="text" name="icon" class="form-control" value="{{ old('icon', $method->icon ?? 'ti-cash') }}"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Komisyon Tipi</label><select name="fee_type" class="form-select"><option value="none">Yok</option><option value="fixed">Sabit</option><option value="percent">Yüzde</option></select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Komisyon</label><input type="number" step="0.0001" name="fee_amount" class="form-control" value="{{ old('fee_amount', $method->fee_amount) }}"></div>
    <div class="col-12 mb-3"><label class="form-label">Dinamik Alanlar (JSON config_schema)</label>
        <textarea name="config_schema_json" class="form-control font-monospace" rows="8" placeholder='{"fields":[{"name":"iban","label":"IBAN","type":"text","required":true}]}'>{{ old('config_schema_json', $method->config_schema ? json_encode($method->config_schema, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '') }}</textarea>
        <small class="text-muted">Her ödeme yöntemi için özel form alanları tanımlayın.</small>
    </div>
    <div class="col-md-6 mb-3"><label class="form-label">Özellikler (JSON array)</label><textarea name="features_json" class="form-control font-monospace" rows="3" placeholder='["multi_currency","online"]'>{{ old('features_json', $method->features ? json_encode($method->features) : '') }}</textarea></div>
    <div class="col-md-6 mb-3"><label class="form-label">Desteklenen Para Birimleri (JSON, boş=tümü)</label><textarea name="supported_currencies_json" class="form-control font-monospace" rows="3" placeholder='["TRY","USD"]'>{{ old('supported_currencies_json', $method->supported_currencies ? json_encode($method->supported_currencies) : '') }}</textarea></div>
    <div class="col-12 mb-3">
        <label class="form-check"><input type="checkbox" name="requires_reference" value="1" class="form-check-input" @checked(old('requires_reference', $method->requires_reference))> Referans zorunlu</label>
        <label class="form-check"><input type="checkbox" name="is_online" value="1" class="form-check-input" @checked(old('is_online', $method->is_online))> Online ödeme</label>
        <label class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $method->is_active ?? true))> Aktif</label>
    </div>
</div>
<button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
<a href="{{ route('settings.payment-methods.index') }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a>
</form></div></div>
@endsection
