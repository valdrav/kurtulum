@php
    $cost = $cost ?? null;
    $shipment = $shipment ?? null;
    $compact = $compact ?? false;
    $redirect = $redirect ?? 'index';
    $statusOptions = app(\App\Services\ShipmentCostService::class)->statusOptions();
@endphp

<div class="row g-2">
    @if($shipment)
    <input type="hidden" name="shipment_id" value="{{ $shipment->id }}">
    @else
    <div class="col-12">
        <label class="form-label">{{ __('app.shipments') }} *</label>
        <select name="shipment_id" class="form-select" required>
            <option value="">—</option>
            @foreach($shipments ?? [] as $s)
            <option value="{{ $s->id }}" @selected(old('shipment_id', $cost?->shipment_id) == $s->id)>{{ $s->displayLabel() }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <div class="col-md-6">
        <label class="form-label">{{ __('logistics.cost_item') }} *</label>
        <input type="text" name="item_name" class="form-control" required maxlength="255"
               value="{{ old('item_name', $cost?->item_name ?: $cost?->description) }}"
               placeholder="{{ __('logistics.cost_item_hint') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('logistics.cost_invoice') }}</label>
        <input type="text" name="invoice_number" class="form-control" maxlength="100"
               value="{{ old('invoice_number', $cost?->invoice_number) }}" placeholder="45.Fatura">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('app.date') }}</label>
        <input type="date" name="expense_date" class="form-control"
               value="{{ old('expense_date', $cost?->expense_date?->format('Y-m-d') ?? date('Y-m-d')) }}">
    </div>

    <div class="col-md-5">
        <label class="form-label">{{ __('logistics.cost_payee') }}</label>
        <input type="text" name="payee" class="form-control" maxlength="255"
               value="{{ old('payee', $cost?->payee) }}" placeholder="{{ __('logistics.cost_payee_hint') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('logistics.cost_country') }}</label>
        <input type="text" name="country" class="form-control" maxlength="100"
               value="{{ old('country', $cost?->country) }}" placeholder="Türkiye, Suriye">
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('logistics.cost_status_label') }} *</label>
        <select name="status" class="form-select" required>
            @foreach($statusOptions as $key => $label)
            <option value="{{ $key }}" @selected(old('status', $cost?->status ?? 'pending') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('app.amount') }} *</label>
        <input type="number" step="0.01" name="amount" class="form-control" required min="0"
               value="{{ old('amount', $cost?->amount) }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('app.currency') }}</label>
        <select name="currency" class="form-select">
            @foreach(registry()->currencyCodes() as $c)
            <option value="{{ $c }}" @selected(old('currency', $cost?->currency ?? 'USD') === $c)>{{ $c }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('logistics.cost_amount_try') }}</label>
        <input type="number" step="0.01" name="amount_try" class="form-control" min="0"
               value="{{ old('amount_try', $cost?->amount_try) }}" placeholder="{{ __('logistics.cost_amount_try_hint') }}">
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('logistics.cost_notes') }}</label>
        <textarea name="notes" class="form-control" rows="{{ $compact ? 2 : 3 }}" maxlength="5000">{{ old('notes', $cost?->notes) }}</textarea>
    </div>

    <input type="hidden" name="redirect" value="{{ $redirect }}">
</div>
