@extends('layouts.app')
@section('title', __('app.reports'))
@section('content')
@include('partials.page-header', ['title' => __('app.reports')])
<div class="row row-cards g-3">
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('reports.sales') }}" class="card card-link h-100">
            <div class="card-body text-center">
                <i class="ti ti-chart-line fs-1 text-blue"></i>
                <h3 class="mt-2 h4">{{ __('reports.sales_title') }}</h3>
                <p class="text-muted small mb-0">{{ __('reports.sales_desc') }}</p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('reports.logistics') }}" class="card card-link h-100">
            <div class="card-body text-center">
                <i class="ti ti-truck fs-1 text-orange"></i>
                <h3 class="mt-2 h4">{{ __('reports.logistics_title') }}</h3>
                <p class="text-muted small mb-0">{{ __('reports.logistics_desc') }}</p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('reports.finance') }}" class="card card-link h-100">
            <div class="card-body text-center">
                <i class="ti ti-report-money fs-1 text-green"></i>
                <h3 class="mt-2 h4">{{ __('finance.profit_loss') }}</h3>
                <p class="text-muted small mb-0">{{ __('reports.finance_desc') }}</p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('reports.customers') }}" class="card card-link h-100">
            <div class="card-body text-center">
                <i class="ti ti-users fs-1 text-purple"></i>
                <h3 class="mt-2 h4">{{ __('reports.customers_title') }}</h3>
                <p class="text-muted small mb-0">{{ __('reports.customers_desc') }}</p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('reports.suppliers') }}" class="card card-link h-100">
            <div class="card-body text-center">
                <i class="ti ti-building-factory fs-1 text-cyan"></i>
                <h3 class="mt-2 h4">{{ __('reports.suppliers_title') }}</h3>
                <p class="text-muted small mb-0">{{ __('reports.suppliers_desc') }}</p>
            </div>
        </a>
    </div>
</div>
@endsection
