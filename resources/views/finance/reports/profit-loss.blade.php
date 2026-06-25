@extends('layouts.app')
@section('title', __('finance.profit_loss'))
@section('content')
@include('partials.page-header', ['title' => __('finance.profit_loss')])
@include('partials.finance-nav')

<div class="card mb-3">
    <div class="card-body py-3">
        @include('partials.finance-period-filter', ['periodMeta' => $periodMeta, 'summary' => $summary, 'uid' => 'report', 'showExtra' => false])
    </div>
</div>

<div class="row row-cards mb-4">
    <div class="col-md-4"><div class="card stat-card"><div class="card-body">
        <div class="subheader">{{ __('finance.income_period') }}</div>
        <div class="h2 text-green mb-0">{{ number_format($summary['income'], 2, ',', '.') }} ₺</div>
    </div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body">
        <div class="subheader">{{ __('finance.expense_period') }}</div>
        <div class="h2 text-red mb-0">{{ number_format($summary['expense'], 2, ',', '.') }} ₺</div>
    </div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body">
        <div class="subheader">{{ __('finance.net_period') }}</div>
        <div class="h2 mb-0 {{ $summary['net'] >= 0 ? 'text-green' : 'text-red' }}">{{ number_format($summary['net'], 2, ',', '.') }} ₺</div>
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
                        @foreach($timeline as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="text-green text-end">{{ number_format($row['income'], 2, ',', '.') }}</td>
                            <td class="text-red text-end">{{ number_format($row['expense'], 2, ',', '.') }}</td>
                            <td class="text-end {{ $row['net'] >= 0 ? 'text-green' : 'text-red' }}"><strong>{{ number_format($row['net'], 2, ',', '.') }}</strong></td>
                        </tr>
                        @endforeach
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
                        <tr><td colspan="2" class="text-muted text-center">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<details class="card">
    <summary class="card-header" style="cursor:pointer"><h3 class="card-title mb-0 d-inline">{{ __('finance.detailed_entries') }}</h3></summary>
    <div class="table-responsive">
        <table class="table table-vcenter table-sm card-table mb-0">
            <thead>
                <tr>
                    <th>{{ __('app.date') }}</th>
                    <th>{{ __('finance.entry_type') }}</th>
                    <th>{{ __('finance.entry_title') }}</th>
                    <th>{{ __('finance.category') }}</th>
                    <th>{{ __('finance.treasury_account') }}</th>
                    <th class="text-end">{{ __('app.amount') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $item)
                <tr>
                    <td>{{ $item->transaction_date->format('d.m.Y') }}</td>
                    <td><span class="badge bg-{{ $item->type==='income'?'success':'danger' }}-lt">{{ $item->type === 'income' ? __('finance.type_income') : __('finance.type_expense') }}</span></td>
                    <td>{{ $item->displayTitle() }}</td>
                    <td class="small">{{ $item->categoryLabel() }}</td>
                    <td class="small">{{ $item->account?->name ?? '—' }}</td>
                    <td class="text-end">{{ number_format($item->amount_base ?? $item->amount, 2, ',', '.') }} ₺</td>
                    <td>@include('partials.income-expense-actions', ['item' => $item])</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</details>
@endsection
