@extends('layouts.app')
@section('title', __('finance.movements'))
@section('content')
@include('partials.page-header', ['title' => __('finance.movements'), 'subtitle' => __('finance.movements_subtitle')])
@include('partials.finance-nav')

<div class="row row-cards mb-3">
    <div class="col-6 col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="subheader small">{{ __('finance.total_cash_balance') }}</div>
            <div class="h3 text-primary mb-0">{{ number_format($totalCash, 2, ',', '.') }} ₺</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="subheader small">{{ __('finance.ledger_credit_period') }}</div>
            <div class="h3 text-green mb-0">{{ number_format($ledgerSummary['credit'], 2, ',', '.') }} ₺</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="subheader small">{{ __('finance.ledger_debit_period') }}</div>
            <div class="h3 text-red mb-0">{{ number_format($ledgerSummary['debit'], 2, ',', '.') }} ₺</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card"><div class="card-body py-3">
            <div class="subheader small">{{ __('finance.ledger_net_period') }}</div>
            <div class="h3 mb-0 {{ $ledgerSummary['net'] >= 0 ? 'text-green' : 'text-red' }}">{{ number_format($ledgerSummary['net'], 2, ',', '.') }} ₺</div>
        </div></div>
    </div>
</div>

@if($treasuryAccounts->isNotEmpty())
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted small me-1">{{ __('finance.treasury_accounts') }}:</span>
            @foreach($treasuryAccounts as $ta)
            <a href="{{ route('finance.accounts.show', $ta) }}" class="badge bg-{{ request('account_id') == $ta->id ? 'primary' : 'azure' }}-lt text-decoration-none">
                {{ $ta->name }} · {{ number_format($ta->balance, 2, ',', '.') }} {{ $ta->currency }}
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="card mb-3">
    <div class="card-body py-3">
        @include('partials.finance-period-filter', [
            'periodMeta' => $periodMeta,
            'summary' => array_merge($summary, ['total_count' => $ledgerSummary['count']]),
            'uid' => 'ledger',
            'showExtra' => true,
            'showLedgerFilters' => true,
            'treasuryAccounts' => $treasuryAccounts,
        ])
    </div>
</div>

<div class="row g-3">
    @if(can_access('finance.create'))
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('finance.quick_entry') }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.income-expenses.store') }}">
                    @csrf
                    @include('partials.income-expense-form', [
                        'treasuryAccounts' => $treasuryAccounts,
                        'paymentMethods' => $paymentMethods,
                        'defaultTreasuryId' => $defaultTreasury->id,
                        'compact' => true,
                        'orders' => $orders ?? [],
                    ])
                    <button type="submit" class="btn btn-primary w-100 mt-2">{{ __('app.save') }}</button>
                </form>
                <div class="text-muted small mt-2">{{ __('finance.income_expense_treasury_info') }}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="col-lg-{{ can_access('finance.create') ? '8' : '12' }}">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('finance.movements') }}</h3>
                <a href="{{ route('finance.profit-loss', request()->only(['period', 'date', 'type', 'search'])) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-chart-bar me-1"></i>{{ __('finance.income_expense_report') }}
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('app.date') }}</th>
                            <th>{{ __('finance.treasury_account') }}</th>
                            <th>{{ __('finance.ledger_source') }}</th>
                            <th>{{ __('app.description') }}</th>
                            <th class="text-end">{{ __('app.amount') }}</th>
                            @if($selectedAccount)
                            <th class="text-end">{{ __('finance.balance_after') }}</th>
                            @endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                        <tr>
                            <td>{{ $movement->transaction_date->format('d.m.Y') }}</td>
                            <td>
                                @if($movement->account)
                                <a href="{{ route('finance.accounts.show', $movement->account) }}">{{ $movement->account->name }}</a>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><span class="badge bg-azure-lt">{{ $ledger->sourceLabel($movement) }}</span></td>
                            <td>
                                <div>{{ $movement->description }}</div>
                                @if($movement->counterpartyLabel())
                                <div class="text-muted small">{{ $movement->counterpartyLabel() }}</div>
                                @endif
                            </td>
                            <td class="text-end {{ $movement->type === 'credit' ? 'text-green' : 'text-red' }}">
                                {{ $movement->type === 'credit' ? '+' : '−' }}{{ format_money((float) $movement->amount, $movement->currency, 2) }}
                            </td>
                            @if($selectedAccount)
                            <td class="text-end fw-bold">
                                {{ format_money($runningBalances[$movement->id] ?? 0, $selectedAccount->currency, 2) }}
                            </td>
                            @endif
                            <td class="text-end">
                                @if(can_access('finance.edit') && $movement->editUrl())
                                <a href="{{ $movement->editUrl() }}" class="btn btn-sm btn-ghost-primary" title="{{ __('finance.edit_transaction') }}"><i class="ti ti-edit"></i></a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="{{ $selectedAccount ? 7 : 6 }}" class="text-muted text-center py-4">{{ __('finance.no_ledger_movements') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($movements->hasPages())<div class="card-footer">{{ $movements->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
