@extends('layouts.app')

@section('title', __('app.dashboard'))

@section('content')
<div class="page-header d-print-none">
    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
            <h2 class="page-title mb-0">{{ __('app.welcome') }}, {{ auth()->user()->name }}</h2>
            <div class="text-muted mt-1">{{ now()->translatedFormat('d F Y, l') }}</div>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-primary btn-sm hide-mobile">
            <i class="ti ti-chart-bar me-1"></i> Raporlar
        </a>
    </div>
</div>

{{-- Mobil KPI --}}
<div class="kpi-scroll d-lg-none mb-3">
    <div class="card stat-card"><div class="card-body p-3">
        <div class="text-muted small">Alacaklar</div>
        @include('partials.dual-money', ['amounts' => $stats['receivables_dual'], 'sizeClass' => 'h3 mb-0'])
    </div></div>
    <div class="card stat-card"><div class="card-body p-3">
        <div class="text-muted small">Aylık Marj</div>
        @include('partials.dual-money', ['amounts' => $stats['monthly_margin_dual'], 'sizeClass' => 'h3 mb-0 text-green'])
    </div></div>
    <div class="card stat-card"><div class="card-body p-3">
        <div class="text-muted small">Kasa Gelir</div>
        <div class="h3 mb-0">{{ format_money($stats['monthly_income'], $stats['treasury_currency']) }}</div>
    </div></div>
    <div class="card stat-card"><div class="card-body p-3">
        <div class="text-muted small">Aktif Sevkiyat</div>
        <div class="h3 mb-0">{{ $stats['shipments_active'] }}</div>
    </div></div>
</div>

<div class="row row-deck row-cards mb-3 hide-mobile">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex justify-content-between"><span class="text-muted">Alacaklar</span><i class="ti ti-wallet text-purple"></i></div>
            @include('partials.dual-money', ['amounts' => $stats['receivables_dual'], 'sizeClass' => 'h1 mb-0'])
            <div class="text-muted small mt-1">{{ __('orders.customer_receivable') }} · {{ __('orders.dual_currency_hint') }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex justify-content-between"><span class="text-muted">Borçlar</span><i class="ti ti-credit-card text-red"></i></div>
            @include('partials.dual-money', ['amounts' => $stats['payables_dual'], 'sizeClass' => 'h1 mb-0 text-red'])
            <div class="text-muted small mt-1">{{ __('orders.supplier_payable') }} · {{ __('orders.dual_currency_hint') }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex justify-content-between"><span class="text-muted">Aylık Marj</span><i class="ti ti-chart-line text-green"></i></div>
            @include('partials.dual-money', ['amounts' => $stats['monthly_margin_dual'], 'sizeClass' => 'h1 mb-0 text-green'])
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex justify-content-between"><span class="text-muted">Kasa (aylık)</span><i class="ti ti-trending-up text-blue"></i></div>
            <div class="h1 mb-0">{{ format_money($stats['monthly_income'], $stats['treasury_currency']) }}</div>
            <div class="text-muted small mt-1">{{ __('finance.treasury') }} gelir · Gider: {{ format_money($stats['monthly_expense'], $stats['treasury_currency']) }}</div>
        </div></div>
    </div>
</div>

<div class="row row-cards mb-3">
    <div class="col-12">
        <div class="card chart-card">
            <div class="card-header">
                <h3 class="card-title">Gelir / Gider / Marj</h3>
                <div class="text-muted small">Son 6 ay · Marj USD + TRY</div>
            </div>
            <div class="card-body pt-0">
                <div id="revenueChart" style="min-height:260px"></div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-6">
        <div class="card chart-card">
            <div class="card-header"><h3 class="card-title">Taşıma Modu</h3></div>
            <div class="card-body pt-0"><div id="modeChart"></div></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Finans Özeti</h3></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="text-muted small">{{ __('orders.customer_receivable') }}</div>
                        @include('partials.dual-money', ['amounts' => $stats['receivables_dual'], 'sizeClass' => 'h2 mb-0'])
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">{{ __('orders.supplier_payable') }}</div>
                        @include('partials.dual-money', ['amounts' => $stats['payables_dual'], 'sizeClass' => 'h2 mb-0 text-red'])
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Toplam ticari marj</div>
                        @include('partials.dual-money', ['amounts' => $stats['margin_dual'], 'sizeClass' => 'h3 mb-0 text-green'])
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">{{ __('app.collections') }} (kasa)</div>
                        <div class="h2 text-green mb-0">{{ format_money($stats['monthly_income'], $stats['treasury_currency']) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">{{ __('app.payments') }} (kasa)</div>
                        <div class="h2 text-red mb-0">{{ format_money($stats['monthly_expense'], $stats['treasury_currency']) }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Net kasa (bu ay)</div>
                        <div class="fw-semibold">{{ format_money($stats['monthly_profit'], $stats['treasury_currency']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($delayedShipments->isNotEmpty() || $overdueTasks->isNotEmpty())
<div class="row row-cards mb-3">
    @if($delayedShipments->isNotEmpty())
    <div class="col-lg-7">
        <div class="card border-warning">
            <div class="card-header bg-warning-lt d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="ti ti-alert-triangle me-1"></i>{{ __('reports.delayed_shipments') }}</h3>
                <a href="{{ route('shipments.index') }}" class="btn btn-sm btn-ghost-secondary">Tümü</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-modern mb-0">
                    <thead><tr><th>No</th><th>Müşteri</th><th>ETA</th><th>{{ __('app.status') }}</th></tr></thead>
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
            <div class="card-header bg-warning-lt">
                <h3 class="card-title mb-0"><i class="ti ti-clock-exclamation me-1"></i>Geciken Görevler</h3>
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

<div class="row row-cards">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ __('app.shipments') }}</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-modern">
                    <thead><tr><th>No</th><th>Müşteri</th><th>ETA</th><th>{{ __('app.status') }}</th></tr></thead>
                    <tbody>
                        @forelse($recentShipments as $s)
                        <tr>
                            <td><a href="{{ route('shipments.show', $s) }}">{{ $s->shipment_number }}</a></td>
                            <td>{{ $s->customer?->company_name ?? '-' }}</td>
                            <td>{{ $s->eta?->format('d.m.Y') ?? '-' }}</td>
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
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('app.orders') }}</h3>
                @if(can_access('orders.create'))
                <a href="{{ route('orders.create') }}" class="btn btn-sm btn-primary">{{ __('app.create') }}</a>
                @endif
            </div>
            <div class="list-group list-group-flush">
                @forelse($recentOrders as $o)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <a href="{{ route('orders.show', $o) }}" class="fw-semibold">{{ $o->order_number }}</a>
                            <div class="text-muted small">{{ $o->customer?->company_name ?? '—' }} · {{ format_money((float) $o->sale_total, $o->currency, 0) }}</div>
                        </div>
                        <div class="d-flex gap-1">
                            @if(can_access('orders.edit'))
                            <a href="{{ route('orders.edit', $o) }}" class="btn btn-sm btn-ghost-primary" title="{{ __('app.edit') }}"><i class="ti ti-edit"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-muted">{{ __('app.no_records') }}</div>
                @endforelse
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Yaklaşan Görevler</h3></div>
            <div class="list-group list-group-flush">
                @forelse($upcomingTasks as $task)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">{{ $task->title }}</div>
                            <div class="text-muted small">{{ $task->due_date?->format('d.m.Y') }} · {{ $task->assignee?->name ?? '-' }}</div>
                        </div>
                        <span class="badge priority-{{ $task->priority }}">{{ $task->priority }}</span>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-muted">{{ __('app.no_records') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isMobile = window.innerWidth < 768;
    const chartData = @json($revenueChart);

    if (typeof ApexCharts !== 'undefined' && document.querySelector('#revenueChart')) {
        new ApexCharts(document.querySelector('#revenueChart'), {
            chart: {
                type: 'area',
                height: isMobile ? 260 : 300,
                toolbar: { show: false },
                zoom: { enabled: false },
                fontFamily: 'Inter, sans-serif',
            },
            series: [
                { name: 'Kasa Gelir (₺)', data: chartData.income },
                { name: 'Kasa Gider (₺)', data: chartData.expense },
                { name: 'Marj (USD)', data: chartData.margin },
                { name: 'Marj (₺)', data: chartData.margin_try },
            ],
            colors: ['#22c55e', '#ef4444', '#6366f1', '#0ea5e9'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: isMobile ? 2 : 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0.02 } },
            xaxis: { categories: chartData.labels, labels: { rotate: isMobile ? -45 : 0, style: { fontSize: '11px' } } },
            yaxis: { labels: { formatter: v => (v >= 1000 ? (v/1000).toFixed(0)+'k' : v) } },
            legend: { position: 'top', horizontalAlign: 'left' },
            grid: { borderColor: 'rgba(148,163,184,0.15)' },
            tooltip: { shared: true, intersect: false },
        }).render();
    }

    const modeData = @json($shipmentsByMode);
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
});
</script>
@endpush
