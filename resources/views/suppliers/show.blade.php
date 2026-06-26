@extends('layouts.app')
@section('title', $supplier->company_name)
@section('content')
@include('partials.page-header', [
    'title' => $supplier->company_name,
    'subtitle' => type_label($supplier->type, 'suppliers') . ' · ' . (country_label($supplier->country) ?: '—'),
])

@if(($unlinkedOrderCount ?? 0) > 0 && can_access('suppliers.edit'))
<div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <span>{{ __('suppliers.unlinked_orders_hint', ['count' => $unlinkedOrderCount]) }}</span>
    <form method="POST" action="{{ route('suppliers.backfill-orders', $supplier) }}" class="mb-0">
        @csrf
        <button type="submit" class="btn btn-sm btn-warning">{{ __('suppliers.backfill_orders') }}</button>
    </form>
</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100"><div class="card-body py-3">
            <div class="subheader small">{{ __('suppliers.order_count') }}</div>
            <div class="h2 mb-0">{{ $summary['order_count'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100"><div class="card-body py-3">
            <div class="subheader small">{{ __('suppliers.purchase_total') }}</div>
            <div class="h2 mb-0">{{ format_money($summary['purchase_total'], $supplier->currency ?? 'USD', 0) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100"><div class="card-body py-3">
            <div class="subheader small">{{ __('suppliers.paid_total') }}</div>
            <div class="h2 mb-0 text-green">{{ format_money($summary['amount_paid'], $supplier->currency ?? 'USD', 0) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100"><div class="card-body py-3">
            <div class="subheader small">{{ __('suppliers.remaining_payable') }}</div>
            <div class="h2 mb-0 text-red">{{ format_money($summary['remaining_payable'], $supplier->currency ?? 'USD', 0) }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <h3 class="h4 mb-2">{{ $supplier->company_name }}</h3>
            @if($supplier->contact_person)<p class="text-muted mb-2">{{ $supplier->contact_person }}</p>@endif
            <dl class="row mb-0 small">
                <dt class="col-5">E-posta</dt><dd class="col-7">{{ $supplier->email ?? '—' }}</dd>
                <dt class="col-5">Telefon</dt><dd class="col-7">{{ $supplier->phone ?? '—' }}</dd>
                <dt class="col-5">Ülke</dt><dd class="col-7">{{ country_label($supplier->country) ?: '—' }}</dd>
                <dt class="col-5">{{ __('app.status') }}</dt><dd class="col-7">{{ type_label($supplier->status, 'suppliers') }}</dd>
            </dl>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-outline-primary btn-sm">{{ __('app.edit') }}</a>
                @if(can_access('orders.create'))
                <a href="{{ route('orders.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i>{{ __('suppliers.new_purchase_order') }}
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
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-orders" type="button">{{ __('suppliers.purchase_orders') }} ({{ $orders->count() }})</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-products" type="button">{{ __('suppliers.products_purchased') }} ({{ $products->count() }})</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-lines" type="button">{{ __('suppliers.line_items') }}</button></li>
            @if(can_access('finance.view'))
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payments" type="button">{{ __('app.payments') }} ({{ $payments->count() }})</button></li>
            @endif
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-costs" type="button">{{ __('suppliers.shipment_costs') }} ({{ $shipmentCosts->count() }})</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-orders">
                <div class="card">
                    @if($orders->isEmpty())
                    <div class="card-body">
                        <div class="alert alert-info mb-0">{{ __('suppliers.no_orders_hint') }}</div>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>{{ __('app.date') }}</th>
                                    <th>{{ __('suppliers.customer_sale') }}</th>
                                    <th class="text-end">{{ __('suppliers.purchase_amount') }}</th>
                                    <th class="text-end">{{ __('orders.paid') }}</th>
                                    <th>{{ __('app.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $o)
                                <tr>
                                    <td><a href="{{ route('orders.show', $o) }}">{{ $o->order_number }}</a></td>
                                    <td>{{ $o->order_date?->format('d.m.Y') }}</td>
                                    <td>{{ $o->customer?->company_name ?? '—' }}</td>
                                    <td class="text-end">{{ format_money((float) $o->purchase_total, $o->currency, 2) }}</td>
                                    <td class="text-end">{{ format_money((float) $o->amount_paid, $o->currency, 2) }}</td>
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
                                    <th>{{ __('suppliers.product') }}</th>
                                    <th class="text-end">{{ __('suppliers.total_qty') }}</th>
                                    <th class="text-end">{{ __('suppliers.order_count') }}</th>
                                    <th class="text-end">{{ __('suppliers.total_purchase') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $p)
                                <tr>
                                    <td>{{ $p->description ?: ($p->product_id ? 'Ürün #'.$p->product_id : '—') }}</td>
                                    <td class="text-end">{{ number_format((float) $p->total_qty, 2, ',', '.') }} {{ $p->unit ?? '' }}</td>
                                    <td class="text-end">{{ $p->order_count }}</td>
                                    <td class="text-end">{{ number_format((float) $p->total_purchase, 2, ',', '.') }}</td>
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
                                    <th>{{ __('suppliers.product') }}</th>
                                    <th class="text-end">{{ __('orders.quantity') }}</th>
                                    <th class="text-end">{{ __('suppliers.purchase_amount') }}</th>
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
                                    <td class="text-end">{{ format_money((float) $line->purchase_total, $line->order?->currency ?? 'USD', 2) }}</td>
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
            <div class="tab-pane fade" id="tab-payments">
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
                                @forelse($payments as $pay)
                                <tr>
                                    <td><a href="{{ route('finance.payments.show', $pay) }}">{{ $pay->payment_number }}</a></td>
                                    <td>{{ $pay->payment_date?->format('d.m.Y') }}</td>
                                    <td>{{ $pay->order?->order_number ?? '—' }}</td>
                                    <td class="text-end">{{ format_money((float) $pay->amount, $pay->currency, 2) }}</td>
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

            <div class="tab-pane fade" id="tab-costs">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('app.date') }}</th>
                                    <th>{{ __('logistics.cost_item') ?? 'Kalem' }}</th>
                                    <th>Sevkiyat</th>
                                    <th class="text-end">{{ __('app.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shipmentCosts as $cost)
                                <tr>
                                    <td>{{ $cost->expense_date?->format('d.m.Y') ?? '—' }}</td>
                                    <td>{{ $cost->displayTitle() }}</td>
                                    <td>
                                        @if($cost->shipment)
                                        <a href="{{ route('shipments.show', $cost->shipment) }}">{{ $cost->shipment->shipment_number }}</a>
                                        @else — @endif
                                    </td>
                                    <td class="text-end">{{ format_money((float) $cost->amount, $cost->currency ?? 'TRY', 2) }}</td>
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
