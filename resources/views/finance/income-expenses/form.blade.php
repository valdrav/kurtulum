@extends('layouts.app')
@section('title', __('finance.edit_income_expense'))
@section('content')
@include('partials.page-header', ['title' => __('finance.edit_income_expense')])
@include('partials.finance-nav')

<div class="card" style="max-width:640px">
    <div class="card-body">
        <form method="POST" action="{{ route('finance.income-expenses.update', $incomeExpense) }}">
            @csrf @method('PUT')
            @include('partials.income-expense-form', [
                'record' => $incomeExpense,
                'treasuryAccounts' => $treasuryAccounts,
                'paymentMethods' => $paymentMethods,
                'defaultTreasuryId' => $defaultTreasury->id,
                'compact' => false,
            ])
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
            <a href="{{ route('finance.income-expenses') }}" class="btn btn-link">{{ __('app.cancel') }}</a>
        </form>
    </div>
</div>
@endsection
