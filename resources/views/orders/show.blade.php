@extends('layouts.app')
@section('title', $order->order_number)
@section('content')
@include('partials.page-header', ['title' => $order->order_number])

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h3 class="card-title">Ticari Detay</h3>
                <a href="{{ route('orders.edit', $order) }}" class="btn btn-sm btn-primary">{{ __('app.edit') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-modern">
                    <thead>
                        <tr>
                            <th>Açıklama</th>
                            <th>Miktar</th>
                            <th>Alış</th>
                            <th>İsk.%</th>
                            <th>Satış</th>
                            <th>Marj</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->purchase_unit_price ?? 0, 2) }}</td>
                            <td>{{ number_format($item->purchase_discount_percent ?? 0, 1) }}%</td>
                            <td>{{ number_format($item->sale_unit_price ?? $item->unit_price, 2) }}</td>
                            <td class="text-green fw-semibold">{{ number_format($item->margin_amount ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Toplam Satış</th>
                            <th>{{ number_format($finance['sale_total'], 2) }} {{ $order->currency }}</th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end">Toplam Alış</th>
                            <th>{{ number_format($finance['purchase_total'], 2) }} {{ $order->currency }}</th>
                        </tr>
                        <tr class="table-success">
                            <th colspan="5" class="text-end">Toplam Marj</th>
                            <th>{{ number_format($finance['margin_total'], 2) }} {{ $order->currency }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($order->shipments->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('orders.shipments') }}</h3>
                @if(can_access('shipments.create'))
                <a href="{{ route('shipments.create', ['order' => $order->uuid]) }}" class="btn btn-sm btn-outline-primary">{{ __('orders.create_shipment') }}</a>
                @endif
            </div>
            <div class="list-group list-group-flush">
                @foreach($order->shipments as $shipment)
                <a href="{{ route('shipments.show', $shipment) }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span>{{ $shipment->shipment_number }}</span>
                    <span class="badge bg-secondary-lt">{{ status_label($shipment->status, 'shipment') }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @elseif(can_access('shipments.create'))
        <div class="card mt-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="text-muted">{{ __('orders.no_shipments') }}</span>
                <a href="{{ route('shipments.create', ['order' => $order->uuid]) }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-truck-delivery me-1"></i>{{ __('orders.create_shipment') }}
                </a>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <dl class="mb-0">
                    <dt>{{ __('app.customers') }}</dt>
                    <dd>{{ $order->customer?->company_name }}</dd>
                    @if($order->supplier)
                    <dt>Tedarikçi</dt>
                    <dd>{{ $order->supplier->company_name }}</dd>
                    @endif
                    <dt>Incoterm</dt>
                    <dd>{{ $order->incoterm ? incoterm_label($order->incoterm) : '-' }}</dd>
                    <dt>{{ __('app.status') }}</dt>
                    <dd><span class="badge">{{ status_label($order->status, 'order') }}</span></dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3 border-primary">
            <div class="card-header bg-primary-lt"><h3 class="card-title mb-0"><i class="ti ti-calculator"></i> {{ __('orders.finance_title') }}</h3></div>
            <div class="card-body">
                @unless($finance['finance_posted'])
                <div class="alert alert-warning py-2 small mb-3">{{ __('orders.finance_not_posted') }}</div>
                @endunless

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="subheader small">{{ __('orders.customer_receivable') }}</div>
                        <div class="fw-bold">{{ number_format($finance['sale_total'], 2) }} {{ $order->currency }}</div>
                        <div class="text-muted small">{{ __('orders.collected') }}: {{ number_format($finance['amount_collected'], 2) }}</div>
                        <div class="text-primary small">{{ __('orders.remaining') }}: {{ number_format($finance['remaining_receivable'], 2) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="subheader small">{{ __('orders.supplier_payable') }}</div>
                        <div class="fw-bold">{{ number_format($finance['purchase_total'], 2) }} {{ $order->currency }}</div>
                        <div class="text-muted small">{{ __('orders.paid') }}: {{ number_format($finance['amount_paid'], 2) }}</div>
                        <div class="text-red small">{{ __('orders.remaining') }}: {{ number_format($finance['remaining_payable'], 2) }}</div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded bg-light">
                    <span class="small">{{ __('orders.expected_margin') }}</span>
                    <strong class="text-green">{{ number_format($finance['margin_total'], 2) }} {{ $order->currency }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded bg-light">
                    <span class="small">{{ __('orders.cash_profit') }}</span>
                    <strong class="{{ $finance['treasury_profit'] >= 0 ? 'text-green' : 'text-red' }}">{{ number_format($finance['treasury_profit'], 2) }} {{ $order->currency }}</strong>
                </div>

                <span class="badge bg-{{ match($finance['finance_status']) { 'settled' => 'success', 'partial' => 'warning', default => 'secondary' } }}-lt">
                    {{ __('orders.finance_status.' . $finance['finance_status']) }}
                </span>
            </div>
        </div>

        @if(can_access('finance.create') && $customerAccount && $finance['remaining_receivable'] > 0)
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('orders.record_collection') }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.collections.store') }}">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <input type="hidden" name="account_id" value="{{ $customerAccount->id }}">
                    <input type="hidden" name="currency" value="{{ $order->currency }}">
                    <div class="mb-2">
                        <label class="form-label">{{ __('app.amount') }}</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="{{ number_format($finance['remaining_receivable'], 2, '.', '') }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('finance.treasury_account') }}</label>
                        <select name="treasury_account_id" class="form-select" required>
                            @foreach($treasuryAccounts as $ta)
                            <option value="{{ $ta->id }}">{{ $ta->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('app.date') }}</label>
                        <input type="date" name="collection_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('finance.payment_method') }}</label>
                        <select name="payment_method_id" class="form-select" required>
                            @foreach($collectionMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">{{ __('orders.record_collection') }}</button>
                </form>
            </div>
        </div>
        @endif

        @if(can_access('finance.create') && $supplierAccount && $finance['remaining_payable'] > 0)
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('orders.record_payment') }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('finance.payments.store') }}">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <input type="hidden" name="account_id" value="{{ $supplierAccount->id }}">
                    <input type="hidden" name="currency" value="{{ $order->currency }}">
                    <div class="mb-2">
                        <label class="form-label">{{ __('app.amount') }}</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="{{ number_format($finance['remaining_payable'], 2, '.', '') }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('finance.treasury_account') }}</label>
                        <select name="treasury_account_id" class="form-select" required>
                            @foreach($treasuryAccounts as $ta)
                            <option value="{{ $ta->id }}">{{ $ta->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('app.date') }}</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('finance.payment_method') }}</label>
                        <select name="payment_method_id" class="form-select" required>
                            @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">{{ __('orders.record_payment') }}</button>
                </form>
            </div>
        </div>
        @endif

        @if($order->collections->isNotEmpty() || $order->payments->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('orders.finance_movements') }}</h3></div>
            <div class="list-group list-group-flush">
                @foreach($order->collections as $c)
                <a href="{{ route('finance.collections.show', $c) }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span class="text-green"><i class="ti ti-arrow-down-left"></i> {{ $c->collection_number }}</span>
                    <strong>+{{ number_format($c->amount, 2) }}</strong>
                </a>
                @endforeach
                @foreach($order->payments as $p)
                <a href="{{ route('finance.payments.show', $p) }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span class="text-red"><i class="ti ti-arrow-up-right"></i> {{ $p->payment_number }}</span>
                    <strong>-{{ number_format($p->amount, 2) }}</strong>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
