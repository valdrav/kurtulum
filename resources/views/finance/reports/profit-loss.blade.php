@extends('layouts.app')
@section('title', __('finance.profit_loss'))
@section('content')
@include('partials.page-header', ['title' => __('finance.profit_loss')])
@include('partials.finance-nav')

<div class="card mb-3">
    <div class="card-body py-3">
        @include('partials.finance-period-filter', [
            'periodMeta' => $periodMeta,
            'summary' => $summary,
            'uid' => 'report',
            'showExtra' => true,
            'showLedgerFilters' => true,
            'treasuryAccounts' => $treasuryAccounts ?? collect(),
        ])
    </div>
</div>

@if(($summary['total_count'] ?? 0) === 0)
<div class="alert alert-info mb-4">
    <i class="ti ti-info-circle me-1"></i>{{ __('dashboard.report_empty_hint') }}
</div>
@else
<div class="alert alert-azure-lt mb-4 py-2">
    <i class="ti ti-info-circle me-1"></i>{{ __('finance.profit_loss_ledger_hint') }}
</div>
@endif

<div class="row row-cards mb-4">
    <div class="col-md-4"><div class="card stat-card"><div class="card-body">
        <div class="subheader">{{ __('finance.income_period') }}</div>
        <div class="h2 text-green mb-0">{{ number_format($summary['income'], 2, ',', '.') }} ₺</div>
        <div class="text-muted small">{{ $summary['income_count'] ?? 0 }} {{ __('finance.records') }}</div>
    </div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body">
        <div class="subheader">{{ __('finance.expense_period') }}</div>
        <div class="h2 text-red mb-0">{{ number_format($summary['expense'], 2, ',', '.') }} ₺</div>
        <div class="text-muted small">{{ $summary['expense_count'] ?? 0 }} {{ __('finance.records') }}</div>
    </div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body">
        <div class="subheader">{{ __('finance.net_period') }}</div>
        <div class="h2 mb-0 {{ $summary['net'] >= 0 ? 'text-green' : 'text-red' }}">{{ number_format($summary['net'], 2, ',', '.') }} ₺</div>
        <div class="text-muted small">{{ $periodMeta['label'] ?? '' }}</div>
    </div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('finance.timeline_breakdown') }}</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm card-table mb-0">
                    <thead><tr><th>{{ __('finance.period_label') }}</th><th class="text-end">{{ __('finance.type_income') }}</th><th class="text-end">{{ __('finance.type_expense') }}</th><th class="text-end">{{ __('finance.net_period') }}</th></tr></thead>
                    <tbody>
                        @forelse($timeline as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="text-green text-end">{{ number_format($row['income'], 2, ',', '.') }}</td>
                            <td class="text-red text-end">{{ number_format($row['expense'], 2, ',', '.') }}</td>
                            <td class="text-end {{ $row['net'] >= 0 ? 'text-green' : 'text-red' }}"><strong>{{ number_format($row['net'], 2, ',', '.') }}</strong></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-muted text-center py-3">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('finance.by_category') }}</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm card-table mb-0">
                    <thead><tr><th>{{ __('finance.category') }}</th><th class="text-end">{{ __('app.amount') }}</th></tr></thead>
                    <tbody>
                        @forelse($byCategory as $row)
                        <tr>
                            <td>
                                {{ $row->category }}
                                <span class="badge bg-{{ $row->type==='income'?'success':'danger' }}-lt ms-1">{{ $row->type === 'income' ? __('finance.type_income') : __('finance.type_expense') }}</span>
                            </td>
                            <td class="text-end">{{ number_format($row->total, 2, ',', '.') }} ₺</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-muted text-center py-3">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if(isset($byTreasury) && $byTreasury->isNotEmpty())
<div class="card mb-4">
    <div class="card-header"><h3 class="card-title mb-0">{{ __('dashboard.by_bank_account') }}</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter table-sm card-table mb-0">
            <thead>
                <tr>
                    <th>{{ __('finance.treasury_account') }}</th>
                    <th class="text-end">{{ __('finance.type_income') }}</th>
                    <th class="text-end">{{ __('finance.type_expense') }}</th>
                    <th class="text-end">{{ __('finance.net_period') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byTreasury as $row)
                <tr>
                    <td>{{ $row->account_name }}</td>
                    <td class="text-green text-end">{{ number_format($row->income, 2, ',', '.') }} ₺</td>
                    <td class="text-red text-end">{{ number_format($row->expense, 2, ',', '.') }} ₺</td>
                    <td class="text-end {{ $row->net >= 0 ? 'text-green' : 'text-red' }}"><strong>{{ number_format($row->net, 2, ',', '.') }} ₺</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<details class="card" @if(($summary['total_count'] ?? 0) > 0) open @endif>
    <summary class="card-header" style="cursor:pointer"><h3 class="card-title mb-0 d-inline">{{ __('finance.detailed_entries') }}</h3></summary>
    <div class="table-responsive">
        <table class="table table-vcenter table-sm card-table mb-0">
            <thead>
                <tr>
                    <th>{{ __('app.date') }}</th>
                    <th>{{ __('finance.ledger_source') }}</th>
                    <th>{{ __('finance.entry_title') }}</th>
                    <th>{{ __('finance.category') }}</th>
                    <th>{{ __('finance.treasury_account') }}</th>
                    <th class="text-end">{{ __('app.amount') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $item)
                @php
                    $entryType = $item->type === 'credit' ? 'income' : 'expense';
                    $category = $ledger->categoryKey($item);
                @endphp
                <tr>
                    <td>{{ $item->transaction_date->format('d.m.Y') }}</td>
                    <td><span class="badge bg-azure-lt">{{ $ledger->sourceLabel($item) }}</span></td>
                    <td>{{ $item->description ?: ($item->counterpartyLabel() ?? '—') }}</td>
                    <td class="small">{{ $category['category'] }}</td>
                    <td class="small">{{ $item->account?->name ?? '—' }}</td>
                    <td class="text-end {{ $entryType === 'income' ? 'text-green' : 'text-red' }}">
                        {{ $entryType === 'income' ? '+' : '−' }}{{ number_format($ledger->amountInDefaultCurrency($item), 2, ',', '.') }} ₺
                    </td>
                    <td>
                        @if($item->editUrl())
                        <a href="{{ $item->editUrl() }}" class="btn btn-sm btn-ghost-secondary" title="{{ __('app.edit') }}"><i class="ti ti-pencil"></i></a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</details>
@endsection
