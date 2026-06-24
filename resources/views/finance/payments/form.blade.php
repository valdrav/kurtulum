@extends('layouts.app')
@section('title', __('finance.edit_payment'))
@section('content')
@include('partials.page-header', ['title' => __('finance.edit_payment')])
@include('partials.finance-nav')

<div class="card" style="max-width:560px">
    <div class="card-body">
        <form method="POST" action="{{ route('finance.payments.update', $payment) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label">{{ __('app.date') }}</label>
                <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', $payment->payment_date->format('Y-m-d')) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Referans</label>
                <input type="text" name="reference" class="form-control" value="{{ old('reference', $payment->reference) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('app.description') }}</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $payment->notes) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
            <a href="{{ route('finance.payments.show', $payment) }}" class="btn btn-ghost-secondary">İptal</a>
        </form>
    </div>
</div>
@endsection
