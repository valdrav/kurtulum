@extends('layouts.app')
@section('title', $supplier->exists ? $supplier->company_name : __('app.create'))
@section('content')
@include('partials.page-header', ['title' => $supplier->exists ? $supplier->company_name : __('app.create').' - '.__('app.suppliers')])
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
            @csrf @if($supplier->exists) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">{{ __('suppliers.company_name') }} *</label><input type="text" name="company_name" class="form-control" value="{{ old('company_name', $supplier->company_name) }}" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">{{ __('customers.contact_person') }}</label><input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $supplier->contact_person) }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('settings.profile_email') }}</label><input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email) }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('settings.company_phone') }}</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone) }}"></div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('customers.country') }}</label>
                    <select name="country" class="form-select">
                        <option value="">—</option>
                        @foreach(country_options() as $code => $name)
                        <option value="{{ $code }}" @selected(country_iso2(old('country', $supplier->country)) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('suppliers.type') }}</label><select name="type" class="form-select">@foreach(['manufacturer','trader','logistics','service'] as $t)<option value="{{ $t }}" @selected(old('type',$supplier->type ?? 'trader')===$t)>{{ type_label($t, 'suppliers') }}</option>@endforeach</select></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('app.status') }}</label><select name="status" class="form-select">@foreach(['active','inactive'] as $s)<option value="{{ $s }}" @selected(old('status',$supplier->status ?? 'active')===$s)>{{ type_label($s, 'suppliers') }}</option>@endforeach</select></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('app.currency') }}</label><select name="currency" class="form-select">@foreach(registry()->currencyCodes() as $cur)<option value="{{ $cur }}" @selected(old('currency',$supplier->currency ?? 'TRY')===$cur)>{{ currency_name($cur) }} ({{ $cur }})</option>@endforeach</select></div>
                <div class="col-12 mb-3"><label class="form-label">{{ __('app.notes') }}</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $supplier->notes) }}</textarea></div>
            </div>
            <div class="d-flex gap-2"><button type="submit" class="btn btn-primary">{{ __('app.save') }}</button><a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a></div>
        </form>
    </div>
</div>
@endsection
