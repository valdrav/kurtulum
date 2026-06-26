@extends('layouts.app')
@section('title', __('reports.sales_title'))
@section('content')
@include('partials.page-header', ['title' => __('reports.sales_title')])
<a href="{{ route('reports.index') }}" class="btn btn-sm btn-ghost-secondary mb-3"><i class="ti ti-arrow-left"></i> {{ __('app.reports') }}</a>

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-auto">
        <label class="form-label small mb-0">{{ __('finance.fiscal_year') }}</label>
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
            @for($y = now()->year; $y >= now()->year - 5; $y--)
            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
            @endfor
        </select>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table mb-0">
            <thead><tr><th>{{ __('reports.month') }}</th><th class="text-end">USD</th><th class="text-end">TRY</th><th class="text-end">{{ __('reports.order_total') }}</th></tr></thead>
            <tbody>
                @foreach($monthly as $row)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::create(null, $row->month)->translatedFormat('F') }}</td>
                    <td class="text-end">{{ number_format($row->usd ?? 0, 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($row->try ?? 0, 2, ',', '.') }}</td>
                    <td class="text-end fw-semibold">{{ number_format($row->total, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td>{{ __('app.total') }}</td>
                    <td class="text-end">{{ number_format($monthly->sum('usd'), 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($monthly->sum('try'), 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($monthly->sum('total'), 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
