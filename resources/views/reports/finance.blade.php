@extends('layouts.app')
@section('title', __('finance.profit_loss'))
@section('content')
@include('partials.page-header', ['title' => __('finance.profit_loss'), 'subtitle' => __('reports.finance_subtitle')])
<a href="{{ route('reports.index') }}" class="btn btn-sm btn-ghost-secondary mb-3"><i class="ti ti-arrow-left"></i> {{ __('app.reports') }}</a>

<div class="card mb-3">
    <div class="card-body py-3">
        @include('partials.finance-period-filter', ['periodMeta' => $periodMeta, 'summary' => $summary, 'uid' => 'reports-finance', 'showExtra' => false])
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

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
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
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('finance.by_category') }}</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm card-table mb-0">
                    <thead><tr><th>{{ __('finance.category') }}</th><th class="text-end">{{ __('app.amount') }}</th></tr></thead>
                    <tbody>
                        @forelse($byCategory as $row)
                        <tr>
                            <td>{{ $row->category }}</td>
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

<div class="mt-3">
    <a href="{{ route('finance.profit-loss', request()->query()) }}" class="btn btn-outline-primary btn-sm">{{ __('finance.open_full_report') }}</a>
</div>
@endsection
