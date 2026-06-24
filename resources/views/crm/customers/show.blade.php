@extends('layouts.app')
@section('title', $customer->company_name)
@section('content')
@include('partials.page-header', ['title' => $customer->company_name, 'createRoute' => null])
<div class="row">
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <h3>{{ $customer->company_name }}</h3>
            <p class="text-muted">{{ $customer->contact_person }}</p>
            <dl class="row">
                <dt class="col-5">E-posta</dt><dd class="col-7">{{ $customer->email ?? '-' }}</dd>
                <dt class="col-5">Telefon</dt><dd class="col-7">{{ $customer->phone ?? '-' }}</dd>
                <dt class="col-5">Ülke</dt><dd class="col-7">{{ $customer->country ?? '-' }}</dd>
                <dt class="col-5">{{ __('app.status') }}</dt><dd class="col-7"><span class="badge">{{ type_label($customer->status, 'customers') }}</span></dd>
                <dt class="col-5">{{ __('customers.type') }}</dt><dd class="col-7">{{ type_label($customer->type, 'customers') }}</dd>
            </dl>
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary btn-sm">{{ __('app.edit') }}</a>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('app.orders') }} ({{ $customer->orders->count() }})</h3></div>
            <div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>No</th><th>{{ __('app.date') }}</th><th>{{ __('app.amount') }}</th><th>{{ __('app.status') }}</th></tr></thead>
            <tbody>@forelse($customer->orders as $o)<tr><td><a href="{{ route('orders.show', $o) }}">{{ $o->order_number }}</a></td><td>{{ $o->order_date->format('d.m.Y') }}</td><td>{{ number_format($o->total_amount,2) }} {{ $o->currency }}</td><td>{{ status_label($o->status, 'order') }}</td></tr>@empty<tr><td colspan="4" class="text-muted">{{ __('app.no_records') }}</td></tr>@endforelse</tbody></table></div>
        </div>
        <div class="card"><div class="card-header"><h3 class="card-title">{{ __('app.shipments') }}</h3></div>
            <div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>No</th><th>Mod</th><th>ETA</th><th>{{ __('app.status') }}</th></tr></thead>
            <tbody>@forelse($customer->shipments as $s)<tr><td><a href="{{ route('shipments.show', $s) }}">{{ $s->shipment_number }}</a></td><td>{{ __('logistics.'.$s->transport_mode) }}</td><td>{{ $s->eta?->format('d.m.Y') ?? '-' }}</td><td>{{ status_label($s->status, 'shipment') }}</td></tr>@empty<tr><td colspan="4" class="text-muted">{{ __('app.no_records') }}</td></tr>@endforelse</tbody></table></div>
        </div>
    </div>
</div>
@endsection
