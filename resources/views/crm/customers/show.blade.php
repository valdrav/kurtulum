@extends('layouts.app')
@section('title', $customer->company_name)
@section('content')
@include('partials.page-header', [
    'title' => $customer->company_name,
    'subtitle' => type_label($customer->type, 'customers') . ' · ' . (country_label($customer->country) ?: '—'),
])

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100"><div class="card-body py-3">
            <div class="subheader small">{{ __('customers.order_count') }}</div>
            <div class="h2 mb-0">{{ $summary['order_count'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100"><div class="card-body py-3">
            <div class="subheader small">{{ __('customers.sale_total') }}</div>
            <div class="h2 mb-0">{{ format_money($summary['sale_total'], $customer->currency ?? 'USD', 0) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100"><div class="card-body py-3">
            <div class="subheader small">{{ __('customers.collected_total') }}</div>
            <div class="h2 mb-0 text-green">{{ format_money($summary['amount_collected'], $customer->currency ?? 'USD', 0) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100"><div class="card-body py-3">
            <div class="subheader small">{{ __('customers.remaining_receivable') }}</div>
            <div class="h2 mb-0 text-red">{{ format_money($summary['remaining_receivable'], $customer->currency ?? 'USD', 0) }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <h3 class="h4 mb-2">{{ $customer->company_name }}</h3>
            @if($customer->contact_person)<p class="text-muted mb-2">{{ $customer->contact_person }}</p>@endif
            <dl class="row mb-0 small">
                <dt class="col-5">E-posta</dt><dd class="col-7">{{ $customer->email ?? '—' }}</dd>
                <dt class="col-5">Telefon</dt><dd class="col-7">{{ $customer->phone ?? '—' }}</dd>
                <dt class="col-5">Ülke</dt><dd class="col-7">{{ country_label($customer->country) ?: '—' }}</dd>
                <dt class="col-5">{{ __('app.status') }}</dt><dd class="col-7">{{ type_label($customer->status, 'customers') }}</dd>
                <dt class="col-5">{{ __('customers.type') }}</dt><dd class="col-7">{{ type_label($customer->type, 'customers') }}</dd>
                @if($customer->credit_limit)
                <dt class="col-5">{{ __('finance.credit_limit') ?? 'Kredi Limiti' }}</dt>
                <dd class="col-7">{{ format_money((float) $customer->credit_limit, $customer->currency ?? 'USD', 0) }}</dd>
                @endif
            </dl>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary btn-sm">{{ __('app.edit') }}</a>
                @if(can_access('orders.create'))
                <a href="{{ route('orders.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i>{{ __('customers.new_sale_order') }}
                </a>
                @endif
            </div>
        </div></div>

        @if(isset($account) && can_access('finance.view'))
        <div class="card"><div class="card-body">
            <div class="subheader">{{ __('finance.cari_accounts') }}</div>
            <div class="h2 mb-1 {{ $account->balance >= 0 ? 'text-green' : 'text-red' }}">{{ format_money($account->balance, $account->currency, 2) }}</div>
            <div class="text-muted small mb-2">{{ __('finance.current_balance') }} · {{ $account->code }}</div>
            <a href="{{ route('finance.accounts.show', $account) }}" class="btn btn-sm btn-outline-primary w-100">
                <i class="ti ti-list-details me-1"></i>{{ __('finance.transactions') }}
            </a>
        </div></div>
        @endif
    </div>

    <div class="col-lg-8">
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-orders" type="button">{{ __('customers.sale_orders') }} ({{ $orders->count() }})</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-products" type="button">{{ __('customers.products_sold') }} ({{ $products->count() }})</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-lines" type="button">{{ __('customers.line_items') }}</button></li>
            @if(can_access('finance.view'))
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-collections" type="button">{{ __('app.collections') }} ({{ $collections->count() }})</button></li>
            @endif
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-shipments" type="button">{{ __('app.shipments') }} ({{ $shipments->count() }})</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-orders">
                <div class="card">
                    @if($orders->isEmpty())
                    <div class="card-body">
                        <div class="alert alert-info mb-0">{{ __('customers.no_orders_hint') }}</div>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>{{ __('app.date') }}</th>
                                    <th>{{ __('orders.supplier_purchase') }}</th>
                                    <th class="text-end">{{ __('customers.sale_amount') }}</th>
                                    <th class="text-end">{{ __('orders.collected') }}</th>
                                    <th>{{ __('app.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $o)
                                <tr>
                                    <td><a href="{{ route('orders.show', $o) }}">{{ $o->order_number }}</a></td>
                                    <td>{{ $o->order_date?->format('d.m.Y') }}</td>
                                    <td>{{ $o->supplier?->company_name ?? '—' }}</td>
                                    <td class="text-end">{{ format_money((float) $o->sale_total, $o->currency, 2) }}</td>
                                    <td class="text-end">{{ format_money((float) $o->amount_collected, $o->currency, 2) }}</td>
                                    <td>{{ status_label($o->status, 'order') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

            <div class="tab-pane fade" id="tab-products">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>{{ __('customers.product') }}</th>
                                    <th class="text-end">{{ __('customers.total_qty') }}</th>
                                    <th class="text-end">{{ __('customers.order_count') }}</th>
                                    <th class="text-end">{{ __('customers.total_sale') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $p)
                                <tr>
                                    <td>{{ $p->description ?: ($p->product_id ? 'Ürün #'.$p->product_id : '—') }}</td>
                                    <td class="text-end">{{ number_format((float) $p->total_qty, 2, ',', '.') }} {{ $p->unit ?? '' }}</td>
                                    <td class="text-end">{{ $p->order_count }}</td>
                                    <td class="text-end">{{ number_format((float) $p->total_sale, 2, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-lines">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-sm">
                            <thead>
                                <tr>
                                    <th>Sipariş</th>
                                    <th>{{ __('customers.product') }}</th>
                                    <th class="text-end">{{ __('orders.quantity') }}</th>
                                    <th class="text-end">{{ __('customers.sale_amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productLines as $line)
                                <tr>
                                    <td>
                                        <a href="{{ route('orders.show', $line->order) }}">{{ $line->order?->order_number }}</a>
                                        <div class="text-muted small">{{ $line->order?->order_date?->format('d.m.Y') }}</div>
                                    </td>
                                    <td>{{ $line->description }}</td>
                                    <td class="text-end">{{ number_format((float) $line->quantity, 2, ',', '.') }} {{ $line->unit }}</td>
                                    <td class="text-end">{{ format_money((float) $line->total, $line->order?->currency ?? 'USD', 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if(can_access('finance.view'))
            <div class="tab-pane fade" id="tab-collections">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-sm">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>{{ __('app.date') }}</th>
                                    <th>Sipariş</th>
                                    <th class="text-end">{{ __('app.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($collections as $col)
                                <tr>
                                    <td><a href="{{ route('finance.collections.show', $col) }}">{{ $col->collection_number }}</a></td>
                                    <td>{{ $col->collection_date?->format('d.m.Y') }}</td>
                                    <td>{{ $col->order?->order_number ?? '—' }}</td>
                                    <td class="text-end">{{ format_money((float) $col->amount, $col->currency, 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="tab-pane fade" id="tab-shipments">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-sm">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Mod</th>
                                    <th>ETA</th>
                                    <th>{{ __('app.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shipments as $s)
                                <tr>
                                    <td><a href="{{ route('shipments.show', $s) }}">{{ $s->shipment_number }}</a></td>
                                    <td>{{ __('logistics.'.$s->transport_mode) }}</td>
                                    <td>{{ $s->eta?->format('d.m.Y') ?? '—' }}</td>
                                    <td>{{ status_label($s->status, 'shipment') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
