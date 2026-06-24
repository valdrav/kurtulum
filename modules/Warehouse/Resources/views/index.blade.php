@extends('layouts.app')
@section('title', 'Depo & Stok')
@section('content')
@include('partials.page-header', ['title' => 'Depo & Stok'])
<div class="row g-3">
    <div class="col-md-4">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">Aktif Depo</div>
            <div class="h2 mb-0">3</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">Stok Kalemi</div>
            <div class="h2 mb-0">128</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">Kritik Seviye</div>
            <div class="h2 mb-0 text-warning">7</div>
        </div></div>
    </div>
</div>
<div class="card mt-3"><div class="card-body">
    <p class="text-muted mb-0">Bu modül <strong>modules/Warehouse</strong> paketinden yüklenir. Sevkiyat oluşturulduğunda stok hareketlerini hook ile bağlayabilirsiniz.</p>
</div></div>
@endsection
