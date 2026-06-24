@extends('layouts.app')
@section('title', $account->exists ? __('finance.edit_account') : __('finance.new_account'))
@section('content')
@include('partials.page-header', ['title' => $account->exists ? __('finance.edit_account') : __('finance.new_account')])
@include('partials.finance-nav')

<div class="card" style="max-width:720px">
    <div class="card-body">
        <form method="POST" action="{{ $account->exists ? route('finance.accounts.update', $account) : route('finance.accounts.store') }}">
            @csrf
            @if($account->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Kod</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $account->code) }}" placeholder="Otomatik" {{ $account->exists ? 'readonly' : '' }}>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Ad *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $account->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tip *</label>
                    <select name="type" class="form-select" required>
                        @foreach(__('finance.account_types') as $key => $label)
                        <option value="{{ $key }}" @selected(old('type', $account->type)===$key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('app.currency') }}</label>
                    <select name="currency" class="form-select">
                        @foreach(registry()->currencyCodes() as $c)
                        <option value="{{ $c }}" @selected(old('currency', $account->currency)===$c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Müşteri</label>
                    <select name="customer_id" class="form-select">
                        <option value="">—</option>
                        @foreach($customers as $cu)
                        <option value="{{ $cu->id }}" @selected(old('customer_id', $account->customer_id)==$cu->id)>{{ $cu->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tedarikçi</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">—</option>
                        @foreach($suppliers as $su)
                        <option value="{{ $su->id }}" @selected(old('supplier_id', $account->supplier_id)==$su->id)>{{ $su->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                @unless($account->exists)
                <div class="col-md-6">
                    <label class="form-label">{{ __('finance.opening_balance') }}</label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', 0) }}">
                </div>
                @endunless
                <div class="col-12">
                    <label class="form-label">{{ __('app.notes') ?? 'Notlar' }}</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes', $account->notes) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-check">
                        <input type="checkbox" name="is_treasury" value="1" class="form-check-input" @checked(old('is_treasury', $account->is_treasury ?? false))>
                        <span class="form-check-label">{{ __('finance.is_treasury_account') }}</span>
                    </label>
                    <small class="text-muted d-block">{{ __('finance.is_treasury_hint') }}</small>
                </div>
                <div class="col-12">
                    <label class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $account->is_active ?? true))>
                        <span class="form-check-label">Aktif</span>
                    </label>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
                <a href="{{ route('finance.accounts') }}" class="btn btn-ghost-secondary">{{ __('app.cancel') ?? 'İptal' }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
