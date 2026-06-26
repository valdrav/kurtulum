@extends('layouts.app')
@section('title', __('reports.exchange_rates_title'))
@section('content')
@include('partials.page-header', ['title' => __('reports.exchange_rates_title')])

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label class="form-label">{{ __('app.from') }}</label>
        <input type="date" name="from" class="form-control" value="{{ $from }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('app.to') }}</label>
        <input type="date" name="to" class="form-control" value="{{ $to }}">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">{{ __('app.filter') }}</button>
    </div>
</form>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('finance.collections') }}</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>{{ __('app.date') }}</th>
                            <th>{{ __('app.amount') }}</th>
                            <th>{{ __('finance.exchange_rate') }}</th>
                            <th>{{ __('app.orders') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($collections as $c)
                        <tr>
                            <td><a href="{{ route('finance.collections.show', $c) }}">{{ $c->collection_number }}</a></td>
                            <td>{{ $c->collection_date->format('d.m.Y') }}</td>
                            <td>{{ number_format($c->amount, 2) }} {{ $c->currency }}</td>
                            <td>{{ number_format($c->exchange_rate, 4) }}</td>
                            <td>{{ $c->order?->order_number ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('finance.payments') }}</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>{{ __('app.date') }}</th>
                            <th>{{ __('app.amount') }}</th>
                            <th>{{ __('finance.exchange_rate') }}</th>
                            <th>{{ __('app.orders') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $p)
                        <tr>
                            <td><a href="{{ route('finance.payments.show', $p) }}">{{ $p->payment_number }}</a></td>
                            <td>{{ $p->payment_date->format('d.m.Y') }}</td>
                            <td>{{ number_format($p->amount, 2) }} {{ $p->currency }}</td>
                            <td>{{ number_format($p->exchange_rate, 4) }}</td>
                            <td>{{ $p->order?->order_number ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
