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

{{-- Mobil KPI kaydırma --}}
<div class="kpi-scroll d-lg-none mb-3">
    <div class="card stat-card"><div class="card-body p-3">
        <div class="stat-icon bg-blue-lt text-blue mb-2"><i class="ti ti-trending-up"></i></div>
        <div class="text-muted small">Aylık Gelir</div>
        <div class="h3 mb-0">{{ format_money($stats['monthly_income'], $stats['currency']) }}</div>
    </div></div>
    <div class="card stat-card"><div class="card-body p-3">
        <div class="stat-icon bg-green-lt text-green mb-2"><i class="ti ti-chart-line"></i></div>
        <div class="text-muted small">Marj</div>
        <div class="h3 mb-0 text-green">{{ format_money($stats['monthly_margin'], $stats['currency']) }}</div>
    </div></div>
    <div class="card stat-card"><div class="card-body p-3">
        <div class="stat-icon bg-orange-lt text-orange mb-2"><i class="ti ti-truck-delivery"></i></div>
        <div class="text-muted small">Aktif Sevkiyat</div>
        <div class="h3 mb-0">{{ $stats['shipments_active'] }}</div>
    </div></div>
    <div class="card stat-card"><div class="card-body p-3">
        <div class="stat-icon bg-red-lt text-red mb-2"><i class="ti ti-checklist"></i></div>
        <div class="text-muted small">Bekleyen Görev</div>
        <div class="h3 mb-0">{{ $stats['tasks_pending'] }}</div>
    </div></div>
</div>

<div class="row row-deck row-cards mb-3 hide-mobile">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex justify-content-between"><span class="text-muted">Aylık Gelir</span><i class="ti ti-trending-up text-blue"></i></div>
            <div class="h1 mb-0">{{ format_money($stats['monthly_income'], $stats['currency']) }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex justify-content-between"><span class="text-muted">Aylık Marj</span><i class="ti ti-chart-line text-green"></i></div>
            <div class="h1 mb-0 text-green">{{ format_money($stats['monthly_margin'], $stats['currency']) }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex justify-content-between"><span class="text-muted">Alacaklar</span><i class="ti ti-wallet text-purple"></i></div>
            <div class="h1 mb-0">{{ format_money($stats['receivables'], $stats['currency']) }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex justify-content-between"><span class="text-muted">Siparişler</span><i class="ti ti-shopping-cart text-orange"></i></div>
            <div class="h1 mb-0">{{ $stats['orders'] }}</div>
        </div></div>
    </div>
</div>

<div class="row row-cards mb-3">
    <div class="col-12">
        <div class="card chart-card">
            <div class="card-header">
                <h3 class="card-title">Gelir / Gider / Marj</h3>
                <div class="text-muted small">Son 6 ay</div>
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
                    <div class="col-6">
                        <div class="text-muted small">{{ __('app.collections') }}</div>
                        <div class="h2 text-green mb-0">{{ format_money($stats['monthly_income'], $stats['currency']) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">{{ __('app.payments') }}</div>
                        <div class="h2 text-red mb-0">{{ format_money($stats['monthly_expense'], $stats['currency']) }}</div>
                    </div>
                    <div class="col-12">
                        <div class="progress progress-sm">
                            @php $ratio = $stats['monthly_income'] > 0 ? min(100, ($stats['monthly_profit'] / $stats['monthly_income']) * 100) : 0; @endphp
                            <div class="progress-bar bg-green" style="width: {{ max(0, $ratio) }}%"></div>
                        </div>
                        <div class="text-muted small mt-1">Net: {{ format_money($stats['monthly_profit'], $stats['currency']) }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Toplam ticari marj</div>
                        <div class="h3 text-green mb-0">{{ format_money($stats['total_margin'], $stats['currency']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
    const modeData = @json($shipmentsByMode);

    if (typeof ApexCharts !== 'undefined' && document.querySelector('#revenueChart')) {
        new ApexCharts(document.querySelector('#revenueChart'), {
            chart: {
                type: 'area',
                height: isMobile ? 260 : 300,
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: { enabled: true, easing: 'easeinout', speed: 600 },
                fontFamily: 'Inter, sans-serif',
            },
            series: [
                { name: 'Gelir', data: chartData.income },
                { name: 'Gider', data: chartData.expense },
                { name: 'Marj', data: chartData.margin },
            ],
            colors: ['#22c55e', '#ef4444', '#6366f1'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: isMobile ? 2 : 3 },
            fill: {
                type: 'gradient',
                gradient: { opacityFrom: 0.35, opacityTo: 0.05 },
            },
            xaxis: { categories: chartData.labels, labels: { rotate: isMobile ? -45 : 0, style: { fontSize: '11px' } } },
            yaxis: { labels: { formatter: v => (v >= 1000 ? (v/1000).toFixed(0)+'k' : v) } },
            legend: { position: 'top', horizontalAlign: 'left' },
            grid: { borderColor: 'rgba(148,163,184,0.15)' },
            tooltip: { shared: true, intersect: false },
        }).render();
    }

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
