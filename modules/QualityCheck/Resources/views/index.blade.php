@extends('layouts.app')
@section('title', 'Kalite Kontrol')
@section('content')
@include('partials.page-header', ['title' => 'Kalite Kontrol'])
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern">
            <thead>
                <tr>
                    <th>Kontrol No</th>
                    <th>Referans</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>QC-2026-001</td>
                    <td>ORD-2026-0001</td>
                    <td><span class="badge bg-success-lt">Onaylandı</span></td>
                    <td>{{ now()->subDays(2)->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td>QC-2026-002</td>
                    <td>SHP-2026-0003</td>
                    <td><span class="badge bg-warning-lt">Bekliyor</span></td>
                    <td>{{ now()->format('d.m.Y') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<div class="alert alert-info mt-3 mb-0">
    <i class="ti ti-info-circle me-1"></i>
    Modül ZIP dosyası yüklediğinizde otomatik olarak <strong>Ayarlar → Modül Yönetimi</strong> listesinde görünür.
</div>
@endsection
