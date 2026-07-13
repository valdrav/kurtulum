@extends('layouts.app')
@section('title', $account->name)
@section('content')
@include('partials.page-header', ['title' => $account->name])
@include('partials.finance-nav')

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card"><div class="card-body">
        <div class="subheader">{{ __('finance.current_balance') }}</div>
        <div class="h1 {{ $account->balance >= 0 ? 'text-green' : 'text-red' }}">{{ format_money($account->balance, $account->currency, 2) }}</div>
        @if($account->is_treasury)
        <div class="text-muted small mt-1">{{ __('finance.movements_subtitle') }}</div>
        @endif
    </div></div></div>
    <div class="col-md-8"><div class="card"><div class="card-body">
        <dl class="row mb-0 small">
            <dt class="col-sm-3">{{ __('finance.account_code') }}</dt><dd class="col-sm-9">{{ $account->code }}</dd>
            <dt class="col-sm-3">{{ __('app.status') }}</dt><dd class="col-sm-9">{{ $account->typeLabel() }}</dd>
            @if($account->customer)<dt class="col-sm-3">{{ __('app.customers') }}</dt><dd class="col-sm-9">{{ $account->customer->company_name }}</dd>@endif
            @if($account->supplier)<dt class="col-sm-3">{{ __('app.suppliers') }}</dt><dd class="col-sm-9">{{ $account->supplier->company_name }}</dd>@endif
            @if($account->notes)<dt class="col-sm-3">{{ __('app.notes') }}</dt><dd class="col-sm-9">{{ $account->notes }}</dd>@endif
        </dl>
        @if(can_access('finance.edit'))
        <a href="{{ route('finance.accounts.edit', $account) }}" class="btn btn-sm btn-outline-primary mt-2"><i class="ti ti-edit"></i> {{ __('app.edit') }}</a>
        @endif
        @if($account->is_treasury)
        <a href="{{ route('finance.income-expenses', ['account_id' => $account->id]) }}" class="btn btn-sm btn-outline-secondary mt-2 ms-1">
            <i class="ti ti-list"></i> {{ __('finance.movements') }}
        </a>
        @endif
    </div></div></div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">{{ __('finance.transactions') }}</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('app.date') }}</th>
                    <th>{{ __('finance.ledger_source') }}</th>
                    <th>{{ __('finance.counterparty') }}</th>
                    <th>{{ __('app.description') }}</th>
                    <th class="text-end">{{ __('app.amount') }}</th>
                    @if($account->is_treasury)
                    <th class="text-end">{{ __('finance.balance_after') }}</th>
                    @endif
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                <tr>
                    <td>{{ $t->transaction_date->format('d.m.Y') }}</td>
                    <td><span class="badge bg-azure-lt">{{ $ledger->sourceLabel($t) }}</span></td>
                    <td>{{ $t->counterpartyLabel() ?: '—' }}</td>
                    <td>{{ $t->description }}</td>
                    <td class="text-end {{ $t->type==='credit'?'text-green':'text-red' }}">
                        {{ $t->type === 'credit' ? '+' : '−' }}{{ format_money((float) $t->amount, $t->currency, 2) }}
                    </td>
                    @if($account->is_treasury)
                    <td class="text-end fw-bold">{{ format_money($runningBalances[$t->id] ?? 0, $account->currency, 2) }}</td>
                    @endif
                    <td class="text-end">
                        @if(can_access('finance.edit') && $t->editUrl())
                        <a href="{{ $t->editUrl() }}" class="btn btn-sm btn-ghost-primary" title="{{ __('finance.edit_transaction') }}"><i class="ti ti-edit"></i></a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="{{ $account->is_treasury ? 7 : 6 }}" class="text-muted text-center">{{ __('finance.no_ledger_movements') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())<div class="card-footer">{{ $transactions->links() }}</div>@endif
</div>
@endsection
