@extends('layouts.app')
@section('title', __('finance.edit_payment'))
@section('content')
@include('partials.page-header', ['title' => __('finance.edit_payment')])
@include('partials.finance-nav')

<div class="card" style="max-width:640px">
    <div class="card-body">
        <p class="text-muted small">{{ __('finance.edit_payment_hint') }}</p>
        <form method="POST" action="{{ route('finance.payments.update', $payment) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label">Cari Hesap *</label>
                <select name="account_id" class="form-select" required>
                    @foreach($accounts as $a)
                    <option value="{{ $a->id }}" @selected(old('account_id', $payment->account_id) == $a->id)>{{ $a->name }} ({{ $a->currency }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('finance.treasury_account') }} *</label>
                <select name="treasury_account_id" class="form-select" required>
                    @foreach($treasuryAccounts as $ta)
                    <option value="{{ $ta->id }}" @selected(old('treasury_account_id', $payment->treasury_account_id) == $ta->id)>{{ $ta->name }} ({{ $ta->currency }})</option>
                    @endforeach
                </select>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('app.amount') }} *</label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $payment->amount) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('app.currency') }} *</label>
                    <select name="currency" class="form-select" required>
                        @foreach(registry()->currencyCodes() as $c)
                        <option value="{{ $c }}" @selected(old('currency', $payment->currency) === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('finance.exchange_rate') }}</label>
                <input type="number" step="0.000001" name="exchange_rate" class="form-control" value="{{ old('exchange_rate', $payment->exchange_rate) }}">
                <div class="form-hint">{{ __('finance.locked_rate_note') }}: {{ number_format((float) $payment->exchange_rate, 4, ',', '.') }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('extensions.payment_method') }} *</label>
                <select name="payment_method_id" class="form-select" required>
                    @foreach($paymentMethods as $m)
                    <option value="{{ $m->id }}" @selected(old('payment_method_id', $payment->payment_method_id) == $m->id)>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('app.date') }} *</label>
                <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', $payment->payment_date->format('Y-m-d')) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('extensions.reference') }}</label>
                <input type="text" name="reference" class="form-control" value="{{ old('reference', $payment->reference) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('app.description') }}</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $payment->notes) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
            <a href="{{ route('finance.accounts.show', $payment->account_id) }}" class="btn btn-ghost-secondary">{{ __('app.cancel') }}</a>
        </form>
    </div>
</div>
@endsection
