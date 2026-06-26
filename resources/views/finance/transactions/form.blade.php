@extends('layouts.app')
@section('title', __('finance.edit_transaction'))
@section('content')
@include('partials.page-header', ['title' => __('finance.edit_transaction')])
@include('partials.finance-nav')

<div class="card" style="max-width:520px">
    <div class="card-body">
        <form method="POST" action="{{ route('finance.transactions.update', $transaction) }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label">{{ __('app.date') }}</label>
                <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('finance.entry_type') }}</label>
                <select name="type" class="form-select" required>
                    <option value="credit" @selected(old('type', $transaction->type) === 'credit')>{{ __('finance.tx_credit') }}</option>
                    <option value="debit" @selected(old('type', $transaction->type) === 'debit')>{{ __('finance.tx_debit') }}</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('app.amount') }}</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $transaction->amount) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('app.description') }}</label>
                <input type="text" name="description" class="form-control" value="{{ old('description', $transaction->description) }}">
            </div>
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
            <a href="{{ route('finance.accounts.show', $transaction->account_id) }}" class="btn btn-ghost-secondary">{{ __('app.cancel') }}</a>
        </form>
    </div>
</div>
@endsection
