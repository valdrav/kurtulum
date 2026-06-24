@extends('layouts.app')
@section('title', $supplier->exists ? $supplier->company_name : __('app.create'))
@section('content')
@include('partials.page-header', ['title' => __('app.suppliers')])
<div class="card"><div class="card-body"><form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">@csrf @if($supplier->exists)@method('PUT')@endif
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">{{ __('suppliers.company_name') }} *</label><input type="text" name="company_name" class="form-control" value="{{ old('company_name', $supplier->company_name) }}" required></div>
<div class="col-md-6 mb-3"><label class="form-label">{{ __('suppliers.type') }}</label><select name="type" class="form-select">@foreach(['manufacturer','trader','logistics','service'] as $t)<option value="{{ $t }}" @selected(old('type',$supplier->type ?? 'trader')===$t)>{{ type_label($t, 'suppliers') }}</option>@endforeach</select></div>
<div class="col-md-6 mb-3"><label class="form-label">{{ __('settings.profile_email') }}</label><input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email) }}"></div>
<div class="col-md-6 mb-3"><label class="form-label">{{ __('app.status') }}</label><select name="status" class="form-select">@foreach(['active','inactive'] as $s)<option value="{{ $s }}" @selected(old('status',$supplier->status ?? 'active')===$s)>{{ type_label($s, 'suppliers') }}</option>@endforeach</select></div>
<div class="col-md-6 mb-3"><label class="form-label">{{ __('app.currency') }}</label><select name="currency" class="form-select">@foreach(config('ticari.currencies') as $c)<option value="{{ $c }}" @selected(old('currency',$supplier->currency ?? 'USD')===$c)>{{ currency_name($c) }} ({{ $c }})</option>@endforeach</select></div>
</div>
<button type="submit" class="btn btn-primary">{{ __('app.save') }}</button></form></div></div>
@endsection
