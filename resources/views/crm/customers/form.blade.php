@extends('layouts.app')
@section('title', $customer->exists ? $customer->company_name : __('app.create'))
@section('content')
@include('partials.page-header', ['title' => $customer->exists ? $customer->company_name : __('app.create').' - '.__('app.customers')])
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}">
            @csrf @if($customer->exists) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">{{ __('customers.company_name') }} *</label><input type="text" name="company_name" class="form-control" value="{{ old('company_name', $customer->company_name) }}" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">{{ __('customers.contact_person') }}</label><input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $customer->contact_person) }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('settings.profile_email') }}</label><input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('settings.company_phone') }}</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}"></div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('customers.country') }}</label>
                    <select name="country" class="form-select">
                        <option value="">—</option>
                        @foreach(country_options() as $code => $name)
                        <option value="{{ $code }}" @selected(country_iso2(old('country', $customer->country)) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('customers.type') }}</label><select name="type" class="form-select">@foreach(['buyer','agent','distributor','partner'] as $t)<option value="{{ $t }}" @selected(old('type',$customer->type ?? 'buyer')===$t)>{{ type_label($t, 'customers') }}</option>@endforeach</select></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('app.status') }}</label><select name="status" class="form-select">@foreach(['active','inactive','prospect'] as $s)<option value="{{ $s }}" @selected(old('status',$customer->status ?? 'active')===$s)>{{ type_label($s, 'customers') }}</option>@endforeach</select></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('app.currency') }}</label><select name="currency" class="form-select">@foreach(config('ticari.currencies') as $cur)<option value="{{ $cur }}" @selected(old('currency',$customer->currency ?? 'TRY')===$cur)>{{ currency_name($cur) }} ({{ $cur }})</option>@endforeach</select></div>
                <div class="col-12 mb-3"><label class="form-label">{{ __('settings.company_address') }}</label><textarea name="address" class="form-control" rows="2">{{ old('address', $customer->address) }}</textarea></div>
                <div class="col-12 mb-3"><label class="form-label">{{ __('app.notes') }}</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $customer->notes) }}</textarea></div>
            </div>
            <div class="d-flex gap-2"><button type="submit" class="btn btn-primary">{{ __('app.save') }}</button><a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a></div>
        </form>
    </div>
</div>
@endsection
