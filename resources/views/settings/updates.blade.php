@extends('layouts.app')
@section('title', __('app.updates'))
@section('content')
@include('partials.page-header', ['title' => __('app.updates')])
<div class="card mb-3"><div class="card-body"><p>Mevcut sürüm: <strong>{{ $updateInfo['current'] ?? config('ticari.version') }}</strong></p>
@if($updateInfo['available'] ?? false)<div class="alert alert-info">Yeni sürüm mevcut: {{ $updateInfo['latest'] }}</div>@else<p class="text-muted">Sistem güncel.</p>@endif</div></div>
<div class="card"><div class="card-header">Manuel Güncelleme (ZIP)</div><div class="card-body"><form method="POST" action="{{ route('settings.updates.apply') }}" enctype="multipart/form-data">@csrf<input type="file" name="package" class="form-control mb-3" accept=".zip" required><button type="submit" class="btn btn-primary">Güncellemeyi Uygula</button></form></div></div>
@endsection
