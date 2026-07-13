@extends('layouts.app')

@section('title', __('app.dashboard'))

@section('content')
@php
    $perms = $permissions ?? [];
    $kpiList = $kpis ?? [];
    $actions = $quick_actions ?? [];
    $chartData = $charts ?? [];
    $recentData = $recent ?? [];
    $alertData = $alerts ?? [];
    $delayedShipments = $alertData['delayed_shipments'] ?? collect();
    $overdueTasks = $alertData['overdue_tasks'] ?? collect();
    $hasAnyWidget = count($kpiList) > 0 || count($actions) > 0 || ! empty($chartData);
@endphp

<div class="page-header d-print-none mb-3">
    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
            <h2 class="page-title mb-0">{{ __('app.welcome') }}, {{ auth()->user()->name }}</h2>
            <div class="text-muted mt-1">{{ __('dashboard.welcome_subtitle') }} · {{ now()->translatedFormat('d F Y, l') }}</div>
        </div>
        @if(($perms['reports'] ?? false) && can_access('reports.view'))
        <a href="{{ route('reports.index') }}" class="btn btn-primary btn-sm hide-mobile">
            <i class="ti ti-chart-bar me-1"></i>{{ __('dashboard.reports') }}
        </a>
        @endif
    </div>
</div>

@if(! $hasAnyWidget)
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="ti ti-lock-off mb-2" style="font-size:2rem"></i>
        <p class="mb-0">{{ __('dashboard.no_access_section') }}</p>
    </div>
</div>
@else

@if(count($actions) > 0)
<div class="card mb-3">
    <div class="card-header py-2">
        <h3 class="card-title mb-0"><i class="ti ti-bolt me-1"></i>{{ __('dashboard.quick_actions') }}</h3>
    </div>
    <div class="card-body py-3">
        <div class="d-flex flex-wrap gap-2">
            @foreach($actions as $action)
            <a href="{{ $action['route'] }}" class="btn btn-{{ $action['color'] ?? 'primary' }} btn-sm">
                <i class="ti {{ $action['icon'] }} me-1"></i>{{ $action['label'] }}
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

@if(count($kpiList) > 0)
<div class="kpi-scroll d-lg-none mb-3">
    @foreach($kpiList as $kpi)
    <div class="card stat-card">
        <div class="card-body p-3">
            @if(! empty($kpi['link']))
            <a href="{{ $kpi['link'] }}" class="text-reset text-decoration-none d-block">
            @endif
                <div class="text-muted small">{{ $kpi['label'] }}</div>
                @if(($kpi['format'] ?? '') === 'dual')
                    @include('partials.dual-money', ['amounts' => $kpi['value'], 'sizeClass' => 'h3 mb-0 text-'.($kpi['color'] ?? 'primary')])
                @elseif(($kpi['format'] ?? '') === 'money')
                    <div class="h3 mb-0 text-{{ $kpi['color'] ?? 'primary' }}">{{ format_money($kpi['value'], $kpi['currency'] ?? $currency) }}</div>
                @else
                    <div class="h3 mb-0 text-{{ $kpi['color'] ?? 'primary' }}">{{ number_format($kpi['value'], 0, ',', '.') }}</div>
                @endif
            @if(! empty($kpi['link']))
            </a>
            @endif
        </div>
    </div>
    @endforeach
</div>

<div class="row row-deck row-cards mb-3 hide-mobile">
    @foreach($kpiList as $kpi)
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted">{{ $kpi['label'] }}</span>
                    <i class="ti {{ $kpi['icon'] ?? 'ti-chart-dots' }} text-{{ $kpi['color'] ?? 'primary' }}"></i>
                </div>
                @if(($kpi['format'] ?? '') === 'dual')
                    @include('partials.dual-money', ['amounts' => $kpi['value'], 'sizeClass' => 'h1 mb-0 text-'.($kpi['color'] ?? 'primary')])
                @elseif(($kpi['format'] ?? '') === 'money')
                    <div class="h1 mb-0 text-{{ $kpi['color'] ?? 'primary' }}">{{ format_money($kpi['value'], $kpi['currency'] ?? $currency) }}</div>
                @else
                    <div class="h1 mb-0 text-{{ $kpi['color'] ?? 'primary' }}">{{ number_format($kpi['value'], 0, ',', '.') }}</div>
                @endif
                @if(! empty($kpi['hint']))
                <div class="text-muted small mt-1">{{ $kpi['hint'] }}</div>
                @endif
                @if(! empty($kpi['link']))
                <a href="{{ $kpi['link'] }}" class="stretched-link"></a>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@if(isset($chartData['revenue']))
<div class="row row-cards mb-3">
    <div class="col-12">
        <div class="card chart-card">
            <div class="card-header">
                <h3 class="card-title">{{ __('dashboard.revenue_chart') }}</h3>
                <div class="text-muted small">{{ __('dashboard.revenue_chart_hint') }}</div>
            </div>
            <div class="card-body pt-0">
                <div id="revenueChart" style="min-height:260px"></div>
            </div>
        </div>
    </div>
</div>
@endif

@if($delayedShipments->isNotEmpty() || $overdueTasks->isNotEmpty())
<div class="row row-cards mb-3">
    @if($delayedShipments->isNotEmpty())
    <div class="col-lg-7">
        <div class="card border-warning">
            <div class="card-header bg-warning-lt d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="ti ti-alert-triangle me-1"></i>{{ __('dashboard.delayed_shipments') }}</h3>
                <a href="{{ route('shipments.index') }}" class="btn btn-sm btn-ghost-secondary">{{ __('finance.view_all') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-modern mb-0">
                    <thead><tr><th>No</th><th>{{ __('app.customer') }}</th><th>ETA</th><th>{{ __('app.status') }}</th></tr></thead>
                    <tbody>
                        @foreach($delayedShipments as $s)
                        <tr>
                            <td><a href="{{ route('shipments.show', $s) }}">{{ $s->shipment_number }}</a></td>
                            <td>{{ $s->customer?->company_name ?? ($s->order?->order_number ?? '—') }}</td>
                            <td class="text-warning fw-semibold">{{ $s->eta?->format('d.m.Y') ?? '—' }}</td>
                            <td><span class="badge">{{ $s->statusDisplay() }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    @if($overdueTasks->isNotEmpty())
    <div class="col-lg-5">
        <div class="card border-warning">
            <div class="card-header bg-warning-lt d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="ti ti-clock-exclamation me-1"></i>{{ __('dashboard.overdue_tasks') }}</h3>
                <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-ghost-secondary">{{ __('finance.view_all') }}</a>
            </div>
            <div class="list-group list-group-flush">
                @foreach($overdueTasks as $task)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">{{ $task->title }}</div>
                            <div class="text-warning small">{{ $task->due_date?->format('d.m.Y') }} · {{ $task->assignee?->name ?? '—' }}</div>
                        </div>
                        <span class="badge priority-{{ $task->priority }}">{{ $task->priority }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endif

<div class="row row-cards mb-3">
    @if(isset($chartData['shipments_by_mode']) && $chartData['shipments_by_mode']->isNotEmpty())
    <div class="col-md-6">
        <div class="card chart-card h-100">
            <div class="card-header"><h3 class="card-title">{{ __('dashboard.shipments_by_mode') }}</h3></div>
            <div class="card-body pt-0"><div id="modeChart"></div></div>
        </div>
    </div>
    @endif

    @if(($perms['finance'] ?? false) && isset($recentData['finance_totals']))
    <div class="col-md-{{ isset($chartData['shipments_by_mode']) && $chartData['shipments_by_mode']->isNotEmpty() ? '6' : '12' }}">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('dashboard.finance_overview') }}</h3>
                <a href="{{ route('finance.treasury') }}" class="btn btn-sm btn-ghost-primary">{{ __('finance.treasury') }}</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted small">{{ __('app.collections') }} ({{ __('dashboard.this_month') }})</div>
                        <div class="h2 text-green mb-0">{{ format_money($recentData['finance_totals']['collections_month'], $currency) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">{{ __('app.payments') }} ({{ __('dashboard.this_month') }})</div>
                        <div class="h2 text-red mb-0">{{ format_money($recentData['finance_totals']['payments_month'], $currency) }}</div>
                    </div>
                    <div class="col-12">
                        <a href="{{ route('finance.profit-loss', ['period' => 'year']) }}" class="btn btn-outline-primary btn-sm w-100">
                            <i class="ti ti-chart-bar me-1"></i>{{ __('finance.profit_loss') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="row row-cards">
    @if(($perms['shipments'] ?? false) && isset($recentData['shipments']))
    <div class="col-lg-{{ ($perms['orders'] ?? false) ? '7' : '12' }}">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('dashboard.recent_shipments') }}</h3>
                <a href="{{ route('shipments.index') }}" class="btn btn-sm btn-ghost-secondary">{{ __('finance.view_all') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-modern mb-0">
                    <thead><tr><th>No</th><th>{{ __('app.customer') }}</th><th>ETA</th><th>{{ __('app.status') }}</th></tr></thead>
                    <tbody>
                        @forelse($recentData['shipments'] as $s)
                        <tr>
                            <td><a href="{{ route('shipments.show', $s) }}">{{ $s->shipment_number }}</a></td>
                            <td>{{ $s->customer?->company_name ?? '—' }}</td>
                            <td>{{ $s->eta?->format('d.m.Y') ?? '—' }}</td>
                            <td><span class="badge">{{ $s->statusDisplay() }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-muted text-center">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="col-lg-5">
        @if(($perms['orders'] ?? false) && isset($recentData['orders']))
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('dashboard.recent_orders') }}</h3>
                @if(can_access('orders.create'))
                <a href="{{ route('orders.create') }}" class="btn btn-sm btn-primary">{{ __('app.create') }}</a>
                @endif
            </div>
            <div class="list-group list-group-flush">
                @forelse($recentData['orders'] as $o)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <a href="{{ route('orders.show', $o) }}" class="fw-semibold">{{ $o->order_number }}</a>
                            <div class="text-muted small">{{ $o->customer?->company_name ?? '—' }} · {{ format_money((float) $o->sale_total, $o->currency, 0) }}</div>
                        </div>
                        @if(can_access('orders.edit'))
                        <a href="{{ route('orders.edit', $o) }}" class="btn btn-sm btn-ghost-primary" title="{{ __('app.edit') }}"><i class="ti ti-edit"></i></a>
                        @endif
                    </div>
                </div>
                @empty
                <div class="list-group-item text-muted">{{ __('app.no_records') }}</div>
                @endforelse
            </div>
        </div>
        @endif

        @if(($perms['finance'] ?? false) && isset($recentData['income_expenses']))
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('dashboard.recent_entries') }}</h3>
                <a href="{{ route('finance.income-expenses') }}" class="btn btn-sm btn-ghost-secondary">{{ __('finance.view_all') }}</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($recentData['income_expenses'] as $item)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">{{ $item->displayTitle() }}</div>
                            <div class="text-muted small">{{ $item->transaction_date->format('d.m.Y') }} · {{ $item->categoryLabel() }}</div>
                        </div>
                        <span class="badge bg-{{ $item->type === 'income' ? 'success' : 'danger' }}-lt">
                            {{ format_money($item->normalizedAmount(), $currency) }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-muted">{{ __('app.no_records') }}</div>
                @endforelse
            </div>
        </div>
        @endif

        @if(($perms['tasks'] ?? false) && isset($recentData['upcoming_tasks']))
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('dashboard.upcoming_tasks') }}</h3></div>
            <div class="list-group list-group-flush">
                @forelse($recentData['upcoming_tasks'] as $task)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">{{ $task->title }}</div>
                            <div class="text-muted small">{{ $task->due_date?->format('d.m.Y') }} · {{ $task->assignee?->name ?? '—' }}</div>
                        </div>
                        <span class="badge priority-{{ $task->priority }}">{{ $task->priority }}</span>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-muted">{{ __('app.no_records') }}</div>
                @endforelse
            </div>
        </div>
        @endif

        @if(($perms['tasks'] ?? false) && isset($recentData['my_tasks']) && $recentData['my_tasks']->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('dashboard.my_tasks') }}</h3></div>
            <div class="list-group list-group-flush">
                @foreach($recentData['my_tasks'] as $task)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">{{ $task->title }}</div>
                            <div class="text-muted small">{{ $task->due_date?->format('d.m.Y') }}</div>
                        </div>
                        <span class="badge priority-{{ $task->priority }}">{{ $task->priority }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@if(($perms['finance'] ?? false) && (isset($recentData['collections']) || isset($recentData['payments'])))
<div class="row row-cards mt-3">
    @if(isset($recentData['collections']))
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('dashboard.recent_collections') }}</h3>
                <a href="{{ route('finance.collections') }}" class="btn btn-sm btn-ghost-secondary">{{ __('finance.view_all') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm mb-0">
                    <thead><tr><th>{{ __('app.date') }}</th><th>{{ __('app.customer') }}</th><th class="text-end">{{ __('app.amount') }}</th></tr></thead>
                    <tbody>
                        @forelse($recentData['collections'] as $c)
                        <tr>
                            <td>{{ $c->collection_date?->format('d.m.Y') }}</td>
                            <td>{{ $c->account?->customer?->company_name ?? '—' }}</td>
                            <td class="text-end text-green">{{ format_money((float) $c->amount * (float) ($c->exchange_rate ?: 1), $c->currency ?? $currency) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-muted text-center">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    @if(isset($recentData['payments']))
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('dashboard.recent_payments') }}</h3>
                <a href="{{ route('finance.payments') }}" class="btn btn-sm btn-ghost-secondary">{{ __('finance.view_all') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm mb-0">
                    <thead><tr><th>{{ __('app.date') }}</th><th>{{ __('app.supplier') }}</th><th class="text-end">{{ __('app.amount') }}</th></tr></thead>
                    <tbody>
                        @forelse($recentData['payments'] as $p)
                        <tr>
                            <td>{{ $p->payment_date?->format('d.m.Y') }}</td>
                            <td>{{ $p->account?->supplier?->company_name ?? '—' }}</td>
                            <td class="text-end text-red">{{ format_money((float) $p->amount * (float) ($p->exchange_rate ?: 1), $p->currency ?? $currency) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-muted text-center">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

@endif
@endsection

@push('scripts')
@if(isset($chartData['revenue']) || (isset($chartData['shipments_by_mode']) && $chartData['shipments_by_mode']->isNotEmpty()))
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isMobile = window.innerWidth < 768;

    @if(isset($chartData['revenue']))
    const revenue = @json($chartData['revenue']);
    if (typeof ApexCharts !== 'undefined' && document.querySelector('#revenueChart')) {
        const series = [
            { name: @json(__('finance.type_income')), data: revenue.income },
            { name: @json(__('finance.type_expense')), data: revenue.expense },
        ];
        if (revenue.margin && revenue.margin.length) {
            series.push({ name: @json(__('dashboard.monthly_margin')) + ' (USD)', data: revenue.margin });
        }
        new ApexCharts(document.querySelector('#revenueChart'), {
            chart: { type: 'area', height: isMobile ? 260 : 300, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'Inter, sans-serif' },
            series: series,
            colors: ['#22c55e', '#ef4444', '#6366f1'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0.02 } },
            xaxis: { categories: revenue.labels, labels: { rotate: isMobile ? -45 : 0, style: { fontSize: '11px' } } },
            yaxis: { labels: { formatter: v => (v >= 1000 ? (v/1000).toFixed(0)+'k' : v) } },
            legend: { position: 'top', horizontalAlign: 'left' },
            grid: { borderColor: 'rgba(148,163,184,0.15)' },
            tooltip: { shared: true, intersect: false },
        }).render();
    }
    @endif

    @if(isset($chartData['shipments_by_mode']) && $chartData['shipments_by_mode']->isNotEmpty())
    const modeData = @json($chartData['shipments_by_mode']);
    if (typeof ApexCharts !== 'undefined' && document.querySelector('#modeChart') && Object.keys(modeData).length) {
        new ApexCharts(document.querySelector('#modeChart'), {
            chart: { type: 'donut', height: isMobile ? 240 : 280, fontFamily: 'Inter, sans-serif' },
            series: Object.values(modeData),
            labels: Object.keys(modeData),
            colors: ['#6366f1', '#22c55e', '#f59e0b', '#06b6d4'],
            legend: { position: 'bottom' },
            plotOptions: { pie: { donut: { size: '68%' } } },
            dataLabels: { enabled: !isMobile },
        }).render();
    }
    @endif
});
</script>
@endif
@endpush
