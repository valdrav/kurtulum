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
                'orders' => $orders ?? [],
            ])
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
            <a href="{{ route('finance.income-expenses') }}" class="btn btn-link">{{ __('app.cancel') }}</a>
            @if(can_access('finance.delete') || can_access('finance.create'))
            <form method="POST" action="{{ route('finance.income-expenses.destroy', $incomeExpense) }}" class="d-inline float-end"
                  onsubmit="return confirm(@json(__('app.confirm_delete')))">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger"><i class="ti ti-trash me-1"></i>{{ __('app.delete') }}</button>
            </form>
            @endif
        </form>
    </div>
</div>
@endsection
