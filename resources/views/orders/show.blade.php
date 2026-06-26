@extends('layouts.app')
@section('title', $order->order_number)
@section('content')
<div class="page-header d-print-none mb-3">
    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
            <h2 class="page-title mb-0">{{ $order->order_number }}</h2>
            <div class="text-muted small">{{ $order->customer?->company_name ?? '—' }} · {{ format_money((float) $order->sale_total, $order->currency, 2) }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($order->trashed() && can_access('orders.delete'))
            <form action="{{ route('orders.restore', $order->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-success btn-sm">
                    <i class="ti ti-rotate me-1"></i>Geri yükle
                </button>
            </form>
            @else
            @if(can_access('orders.edit'))
            <a href="{{ route('orders.edit', $order) }}" class="btn btn-primary btn-sm">
                <i class="ti ti-edit me-1"></i>{{ __('orders.edit_order') }}
            </a>
            @endif
            @if($order->status !== 'cancelled' && can_access('orders.edit'))
            <form action="{{ route('orders.cancel', $order) }}" method="POST" class="d-inline"
                  onsubmit="return confirm(@json(__('orders.cancel_confirm')))">
                @csrf
                <button type="submit" class="btn btn-outline-warning btn-sm">
                    <i class="ti ti-ban me-1"></i>İptal et
                </button>
            </form>
            @endif
            @if(can_access('orders.create'))
            <form action="{{ route('orders.duplicate', $order) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-copy me-1"></i>Kopyala
                </button>
            </form>
            @endif
            @if(can_access('emails.create'))
            <a href="{{ route('emails.compose', ['link_type' => 'order', 'link_id' => $order->id]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-mail me-1"></i>E-posta
            </a>
            @endif
            @if(can_access('orders.delete'))
            <form action="{{ route('orders.destroy', $order) }}" method="POST" class="d-inline"
                  onsubmit="return confirm(@json(__('orders.delete_confirm')))">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="ti ti-trash me-1"></i>{{ __('app.delete') }}
                </button>
            </form>
            @endif
            @endif
        </div>
    </div>
</div>

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
                @if(($finance['order_expenses'] ?? 0) > 0)
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded bg-light">
                    <span class="small">{{ __('orders.order_expenses') }}</span>
                    <strong class="text-red">−{{ number_format($finance['order_expenses'], 2) }} {{ $order->currency }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded bg-light">
                    <span class="small">{{ __('orders.net_margin') }}</span>
                    <strong class="{{ ($finance['net_margin'] ?? 0) >= 0 ? 'text-green' : 'text-red' }}">{{ number_format($finance['net_margin'], 2) }} {{ $order->currency }}</strong>
                </div>
                @if(!empty($finance['order_expense_items']))
                <details class="mb-3 small">
                    <summary class="text-muted mb-2" style="cursor:pointer">{{ __('orders.order_expense_breakdown') }}</summary>
                    <ul class="list-unstyled mb-0">
                        @foreach($finance['order_expense_items'] as $expense)
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span>{{ $expense['label'] }} <span class="text-muted">({{ $expense['meta'] }})</span></span>
                            <span class="text-red">−{{ number_format($expense['amount'], 2) }}</span>
                        </li>
                        @endforeach
                    </ul>
                </details>
                @endif
                @endif
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
                        <input type="number" step="0.01" name="amount" class="form-control" id="collection-amount" value="{{ number_format($finance['remaining_receivable'], 2, '.', '') }}" required>
                        @if($order->currency !== 'TRY')
                        <div class="text-muted small mt-1" id="collection-try-preview"></div>
                        @endif
                    </div>
                    @if($order->currency !== 'TRY')
                    <div class="mb-2">
                        <label class="form-label">{{ __('finance.exchange_rate') }}</label>
                        <input type="number" step="0.000001" name="exchange_rate" class="form-control" id="collection-rate" min="0.000001"
                            value="{{ old('exchange_rate', $fxRates[$order->currency] ?? '') }}"
                            placeholder="1 {{ $order->currency }} = ? TRY">
                        <div class="form-hint">{{ __('finance.transaction_rate_hint', ['currency' => $order->currency]) }}</div>
                    </div>
                    @endif
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
                        <input type="number" step="0.01" name="amount" class="form-control" id="payment-amount" value="{{ number_format($finance['remaining_payable'], 2, '.', '') }}" required>
                        @if($order->currency !== 'TRY')
                        <div class="text-muted small mt-1" id="payment-try-preview"></div>
                        @endif
                    </div>
                    @if($order->currency !== 'TRY')
                    <div class="mb-2">
                        <label class="form-label">{{ __('finance.exchange_rate') }}</label>
                        <input type="number" step="0.000001" name="exchange_rate" class="form-control" id="payment-rate" min="0.000001"
                            value="{{ old('exchange_rate', $fxRates[$order->currency] ?? '') }}"
                            placeholder="1 {{ $order->currency }} = ? TRY">
                        <div class="form-hint">{{ __('finance.transaction_rate_hint', ['currency' => $order->currency]) }}</div>
                    </div>
                    @endif
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

        @if($orderExpenses->isNotEmpty() || can_access('finance.create'))
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('orders.order_expenses') }}</h3>
                @if(can_access('finance.create'))
                <a href="{{ route('finance.income-expenses', ['order_id' => $order->id]) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-plus me-1"></i>Gider ekle
                </a>
                @endif
            </div>
            <div class="list-group list-group-flush">
                @forelse($orderExpenses as $expense)
                <a href="{{ route('finance.income-expenses.edit', $expense) }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span>{{ $expense->displayTitle() }}</span>
                    <span class="text-red">−{{ number_format($expense->amount, 2, ',', '.') }} {{ $expense->currency }}</span>
                </a>
                @empty
                <div class="list-group-item text-muted small">Henüz gider kaydı yok.</div>
                @endforelse
            </div>
        </div>
        @endif

        @if($order->collections->isNotEmpty() || $order->payments->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('orders.finance_movements') }}</h3></div>
            <div class="list-group list-group-flush">
                @foreach($order->collections as $c)
                <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                    <a href="{{ route('finance.collections.show', $c) }}" class="flex-grow-1 text-decoration-none text-reset d-flex justify-content-between">
                        <span class="text-green"><i class="ti ti-arrow-down-left"></i> {{ $c->collection_number }}</span>
                        <span class="text-end">
                            <strong>+{{ number_format($c->amount, 2) }} {{ $c->currency }}</strong>
                            @if($try = format_try_equivalent((float)$c->amount, $c->currency, (float)$c->exchange_rate))
                            <div class="text-muted small">{{ $try }}</div>
                            @endif
                        </span>
                    </a>
                    @if(can_access('finance.delete'))
                    @include('partials.delete-form', ['action' => route('finance.collections.destroy', $c), 'confirm' => __('app.confirm_delete'), 'class' => 'btn-sm btn-ghost-danger', 'iconOnly' => true])
                    @endif
                </div>
                @endforeach
                @foreach($order->payments as $p)
                <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                    <a href="{{ route('finance.payments.show', $p) }}" class="flex-grow-1 text-decoration-none text-reset d-flex justify-content-between">
                        <span class="text-red"><i class="ti ti-arrow-up-right"></i> {{ $p->payment_number }}</span>
                        <span class="text-end">
                            <strong>-{{ number_format($p->amount, 2) }} {{ $p->currency }}</strong>
                            @if($try = format_try_equivalent((float)$p->amount, $p->currency, (float)$p->exchange_rate))
                            <div class="text-muted small">{{ $try }}</div>
                            @endif
                        </span>
                    </a>
                    @if(can_access('finance.delete'))
                    @include('partials.delete-form', ['action' => route('finance.payments.destroy', $p), 'confirm' => __('app.confirm_delete'), 'class' => 'btn-sm btn-ghost-danger', 'iconOnly' => true])
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@if($order->currency !== 'TRY')
@push('scripts')
<script>
(function () {
    function bindTryPreview(amountId, rateId, previewId) {
        const amountEl = document.getElementById(amountId);
        const rateEl = document.getElementById(rateId);
        const previewEl = document.getElementById(previewId);
        if (!amountEl || !rateEl || !previewEl) return;
        const update = () => {
            const amount = parseFloat(amountEl.value) || 0;
            const rate = parseFloat(rateEl.value) || 0;
            previewEl.textContent = rate > 0
                ? '≈ ' + (amount * rate).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ₺'
                : '';
        };
        amountEl.addEventListener('input', update);
        rateEl.addEventListener('input', update);
    }
    bindTryPreview('collection-amount', 'collection-rate', 'collection-try-preview');
    bindTryPreview('payment-amount', 'payment-rate', 'payment-try-preview');
})();
</script>
@endpush
@endif
