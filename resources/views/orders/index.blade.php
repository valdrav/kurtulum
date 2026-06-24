@extends('layouts.app')
@section('title', __('app.orders'))
@section('content')
@include('partials.page-header', ['title' => __('app.orders'), 'createRoute' => route('orders.create'), 'createPermission' => 'orders.create'])

<div class="d-md-none ef-mobile-list mb-3">
    @forelse($orders as $o)
    @include('partials.mobile-record-card', [
        'url' => route('orders.show', $o),
        'title' => $o->order_number,
        'subtitle' => $o->customer?->company_name,
        'meta' => $o->order_date->format('d.m.Y').' · '.number_format($o->total_amount, 2).' '.$o->currency,
        'badge' => status_label($o->status, 'order'),
        'editUrl' => route('orders.edit', $o),
        'editPermission' => 'orders.edit',
    ])
    @empty
    <div class="card"><div class="card-body text-muted">{{ __('app.no_records') }}</div></div>
    @endforelse
</div>

<div class="card hide-mobile">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern">
            <thead><tr><th>No</th><th>{{ __('app.customers') }}</th><th>{{ __('app.date') }}</th><th>{{ __('app.amount') }}</th><th>{{ __('app.status') }}</th><th></th></tr></thead>
            <tbody>
                @forelse($orders as $o)
                <tr>
                    <td><a href="{{ route('orders.show', $o) }}">{{ $o->order_number }}</a></td>
                    <td>{{ $o->customer?->company_name }}</td>
                    <td>{{ $o->order_date->format('d.m.Y') }}</td>
                    <td>{{ number_format($o->total_amount,2) }} {{ $o->currency }}</td>
                    <td>{{ status_label($o->status, 'order') }}</td>
                    <td>
                        @if(can_access('orders.edit'))
                        <a href="{{ route('orders.edit', $o) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())<div class="card-footer">{{ $orders->links() }}</div>@endif
</div>
@if($orders->hasPages())<div class="d-md-none mt-2">{{ $orders->links() }}</div>@endif
@endsection
