@extends('layouts.app')
@section('title', __('reports.suppliers_title'))
@section('content')
@include('partials.page-header', ['title' => __('reports.suppliers_title'), 'subtitle' => __('reports.suppliers_desc')])

<div class="row row-cards g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card h-100"><div class="card-body">
            <div class="subheader">{{ __('reports.total_suppliers') }}</div>
            <div class="h1 mb-0">{{ $totalCount }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100"><div class="card-body">
            <div class="subheader">{{ __('reports.active_suppliers') }}</div>
            <div class="h1 mb-0 text-green">{{ $activeCount }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100"><div class="card-body">
            <div class="subheader">{{ __('reports.suppliers_with_orders') }}</div>
            <div class="h1 mb-0">{{ $topSuppliers->where('orders_count', '>', 0)->count() }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('reports.top_suppliers') }}</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ __('suppliers.company_name') }}</th>
                            <th>{{ __('customers.country') }}</th>
                            <th>{{ __('suppliers.type') }}</th>
                            <th class="text-end">{{ __('app.orders') }}</th>
                            <th class="text-end">Alış Toplamı</th>
                            <th>{{ __('app.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topSuppliers as $s)
                        <tr>
                            <td><a href="{{ route('suppliers.show', $s) }}">{{ $s->company_name }}</a></td>
                            <td>{{ country_label($s->country) ?: '—' }}</td>
                            <td>{{ type_label($s->type, 'suppliers') }}</td>
                            <td class="text-end">{{ $s->orders_count }}</td>
                            <td class="text-end">{{ number_format((float) ($s->orders_sum_purchase_total ?? 0), 2, ',', '.') }}</td>
                            <td><span class="badge bg-{{ $s->status === 'active' ? 'success' : 'secondary' }}-lt">{{ type_label($s->status, 'suppliers') }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-muted">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('reports.by_type') }}</h3></div>
            <ul class="list-group list-group-flush">
                @foreach($byType as $row)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ type_label($row->type, 'suppliers') }}</span>
                    <strong>{{ $row->count }}</strong>
                </li>
                @endforeach
            </ul>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('reports.by_country') }}</h3></div>
            <ul class="list-group list-group-flush">
                @foreach($byCountry as $row)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ country_label($row->country) ?: $row->country }}</span>
                    <strong>{{ $row->count }}</strong>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
