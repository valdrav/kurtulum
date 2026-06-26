@extends('layouts.app')
@section('title', $order->exists ? __('orders.edit_order') . ': ' . $order->order_number : __('app.create'))
@section('content')
@include('partials.page-header', [
    'title' => $order->exists ? __('orders.edit_order') : __('app.create'),
    'subtitle' => $order->exists ? $order->order_number : __('app.orders'),
])

@php
$productsJson = collect($products ?? [])->map(fn ($p) => [
    'id' => $p->id,
    'name' => $p->name,
    'sku' => $p->sku,
    'sale_price' => (float) ($p->unit_price ?? 0),
    'purchase_price' => (float) ($p->unit_price ?? 0),
])->values();
$items = old('items', $order->exists ? $order->items->map(fn($i) => [
    'product_id' => $i->product_id,
    'description' => $i->description,
    'quantity' => $i->quantity,
    'purchase_unit_price' => $i->purchase_unit_price ?? 0,
    'purchase_discount_percent' => $i->purchase_discount_percent ?? 0,
    'sale_unit_price' => $i->sale_unit_price ?? $i->unit_price,
])->toArray() : [['product_id'=>null,'description'=>'','quantity'=>1,'purchase_unit_price'=>0,'purchase_discount_percent'=>0,'sale_unit_price'=>0]]);
@endphp

<div class="card" x-data="orderTradeForm('{{ old('currency', $order->currency ?? 'USD') }}', @js($productsJson))">
    <div class="card-body">
        <form method="POST" action="{{ $order->exists ? route('orders.update', $order) : route('orders.store') }}" id="orderForm">
            @csrf
            @if($order->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('orders.order_number') }}</label>
                    <input type="text" name="order_number" class="form-control @error('order_number') is-invalid @enderror"
                        value="{{ old('order_number', $order->order_number ?? '') }}"
                        placeholder="{{ $order->exists ? '' : __('orders.order_number_auto') }}">
                    @error('order_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-hint">{{ __('orders.order_number_hint') }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('app.customers') }}</label>
                    <select name="customer_id" class="form-select">
                        <option value="">—</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $order->customer_id)==$c->id)>{{ $c->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('orders.supplier_purchase') }} *</label>
                    <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                        <option value="">— {{ __('orders.supplier_purchase_hint') }}</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(old('supplier_id', $order->supplier_id ?? '')==$s->id)>{{ $s->company_name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-hint">{{ __('orders.supplier_purchase_hint') }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">{{ __('app.date') }}</label>
                    <input type="date" name="order_date" class="form-control" value="{{ old('order_date', $order->order_date?->format('Y-m-d') ?? date('Y-m-d')) }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">{{ __('app.status') }}</label>
                    @include('partials.status-select', ['group' => 'order', 'selected' => old('status', $order->status ?? 'draft')])
                </div>
                <div class="col-6 col-md-3">
                    @include('partials.incoterm-field', ['selected' => old('incoterm', $order->incoterm ?? '')])
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">{{ __('app.currency') }}</label>
                    <select name="currency" class="form-select" x-model="currency" @change="updateLabels()">
                        @foreach(config('ticari.currencies') as $c)
                        <option value="{{ $c }}" @selected(old('currency', $order->currency ?? 'USD')===$c)>{{ currency_name($c) }} ({{ $c }})</option>
                        @endforeach
                    </select>
                    <div class="form-hint">{{ __('orders.currency_note') }}</div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
                <h4 class="mb-0">{{ __('orders.trade_items') }}</h4>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="addRow()"><i class="ti ti-plus"></i> {{ __('orders.add_item') }}</button>
            </div>
            <p class="text-muted small">{{ __('orders.trade_items_hint') }}</p>

            <div id="items">
                <datalist id="product-catalog">
                    @foreach($products ?? [] as $p)
                    <option value="{{ $p->name }}">{{ $p->sku ? $p->sku.' — ' : '' }}{{ $p->name }}</option>
                    @endforeach
                </datalist>

                <template x-for="(row, index) in rows" :key="index">
                    <div class="trade-row">
                        <div class="row g-2">
                            <div class="col-12">
                                <input type="hidden" :name="'items['+index+'][product_id]'" :value="row.product_id || ''">
                                <input type="text" :name="'items['+index+'][description]'" class="form-control" list="product-catalog"
                                    placeholder="{{ __('orders.item_description') }}" x-model="row.description"
                                    @change="applyProduct(row, row.description)">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small text-muted">{{ __('orders.quantity') }}</label>
                                <input type="number" step="0.001" :name="'items['+index+'][quantity]'" class="form-control" x-model.number="row.quantity">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small text-muted" x-text="purchaseLabel"></label>
                                <input type="number" step="0.01" :name="'items['+index+'][purchase_unit_price]'" class="form-control" x-model.number="row.purchase_unit_price">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small text-muted">{{ __('orders.discount') }}</label>
                                <input type="number" step="0.01" :name="'items['+index+'][purchase_discount_percent]'" class="form-control" x-model.number="row.purchase_discount_percent">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small text-muted" x-text="saleLabel"></label>
                                <input type="number" step="0.01" :name="'items['+index+'][sale_unit_price]'" class="form-control" x-model.number="row.sale_unit_price">
                            </div>
                            <div class="col-6 col-md-3 d-flex align-items-end">
                                <div class="w-100">
                                    <div class="text-muted small">{{ __('orders.margin') }}</div>
                                    <div class="margin-badge" x-text="formatMargin(row)"></div>
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-end" x-show="rows.length > 1">
                                <button type="button" class="btn btn-ghost-danger btn-sm" @click="removeRow(index)"><i class="ti ti-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="card bg-light mt-3">
                <div class="card-body py-3 d-flex flex-wrap gap-4">
                    <div><span class="text-muted small">{{ __('orders.total_purchase') }}</span><div class="fw-bold" x-text="totals.purchase.toFixed(2) + ' ' + currency"></div></div>
                    <div><span class="text-muted small">{{ __('orders.total_sale') }}</span><div class="fw-bold" x-text="totals.sale.toFixed(2) + ' ' + currency"></div></div>
                    <div><span class="text-muted small">{{ __('orders.total_margin') }}</span><div class="fw-bold text-green" x-text="totals.margin.toFixed(2) + ' ' + currency"></div></div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3 w-100 w-md-auto">{{ __('app.save') }}</button>
            @if($order->exists)
            <a href="{{ route('orders.show', $order) }}" class="btn btn-link mt-3">{{ __('app.cancel') }}</a>
            @endif
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function orderTradeForm(initialCurrency, productsJson) {
    const initial = @json($items);
    const products = productsJson || [];
    return {
        currency: initialCurrency || 'USD',
        products,
        purchaseLabel: '{{ __('orders.purchase_price') }} (USD)',
        saleLabel: '{{ __('orders.sale_price') }} (USD)',
        rows: initial.length ? initial : [{ product_id: null, description: '', quantity: 1, purchase_unit_price: 0, purchase_discount_percent: 0, sale_unit_price: 0 }],
        init() { this.updateLabels(); },
        updateLabels() {
            this.purchaseLabel = '{{ __('orders.purchase_price') }} (' + this.currency + ')';
            this.saleLabel = '{{ __('orders.sale_price') }} (' + this.currency + ')';
        },
        applyProduct(row, name) {
            const product = this.products.find(p => p.name === name);
            if (!product) return;
            row.product_id = product.id;
            row.description = product.name;
            row.purchase_unit_price = product.purchase_price;
            row.sale_unit_price = product.sale_price;
        },
        get totals() {
            return this.rows.reduce((acc, row) => {
                const qty = parseFloat(row.quantity) || 0;
                const purchase = (parseFloat(row.purchase_unit_price) || 0) * (1 - (parseFloat(row.purchase_discount_percent) || 0) / 100);
                const sale = parseFloat(row.sale_unit_price) || 0;
                acc.purchase += qty * purchase;
                acc.sale += qty * sale;
                acc.margin += qty * sale - qty * purchase;
                return acc;
            }, { purchase: 0, sale: 0, margin: 0 });
        },
        addRow() {
            this.rows.push({ product_id: null, description: '', quantity: 1, purchase_unit_price: 0, purchase_discount_percent: 0, sale_unit_price: 0 });
        },
        removeRow(i) {
            this.rows.splice(i, 1);
        },
        formatMargin(row) {
            const qty = parseFloat(row.quantity) || 0;
            const purchase = (parseFloat(row.purchase_unit_price) || 0) * (1 - (parseFloat(row.purchase_discount_percent) || 0) / 100);
            const sale = parseFloat(row.sale_unit_price) || 0;
            const m = qty * sale - qty * purchase;
            const pct = sale > 0 ? ((sale - purchase) / sale * 100).toFixed(1) : 0;
            return m.toFixed(2) + ' (' + pct + '%)';
        }
    }
}
</script>
@endpush
