@php
    $record = $record ?? null;
    $compact = $compact ?? true;
    $treasuryAccounts = $treasuryAccounts ?? company_treasury()->accounts();
    $singleTreasury = $treasuryAccounts->count() === 1;
    $defaultType = old('type', $record?->type ?? 'expense');
@endphp

<div class="income-expense-form">
    <div class="row g-2 mb-2">
        <div class="col-5">
            <label class="form-label">{{ __('finance.entry_type') }} *</label>
            <select name="type" class="form-select" required id="ie-type">
                <option value="expense" @selected($defaultType === 'expense')>{{ __('finance.type_expense') }}</option>
                <option value="income" @selected($defaultType === 'income')>{{ __('finance.type_income') }}</option>
            </select>
        </div>
        <div class="col-7">
            <label class="form-label">{{ __('app.date') }} *</label>
            <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', $record?->transaction_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
        </div>
    </div>

    <div class="mb-2">
        <label class="form-label">{{ __('finance.entry_title') }} *</label>
        <input type="text" name="item_name" class="form-control" list="ie-item-suggestions" value="{{ old('item_name', $record?->item_name ?: $record?->description) }}" placeholder="{{ __('finance.entry_title_hint') }}" required maxlength="200">
        <small class="text-muted">{{ __('finance.category_auto_hint') }}</small>
        <datalist id="ie-item-suggestions">
            @foreach(finance_categories()->itemSuggestions() as $suggestion)
            <option value="{{ $suggestion }}"></option>
            @endforeach
        </datalist>
    </div>

    <div class="row g-2 mb-2">
        <div class="col-7">
            <label class="form-label">{{ __('app.amount') }} *</label>
            <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $record?->amount) }}" required min="0.01">
        </div>
        <div class="col-5">
            <label class="form-label">{{ __('app.currency') }}</label>
            <select name="currency" class="form-select" id="ie-currency">
                @foreach(registry()->currencyCodes() as $c)
                <option value="{{ $c }}" @selected(old('currency', $record?->currency ?? 'TRY') === $c)>{{ $c }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-2" id="ie-exchange-wrap" style="display:none">
        <label class="form-label">{{ __('finance.exchange_rate') }}</label>
        <input type="number" step="0.000001" name="exchange_rate" class="form-control form-control-sm"
               value="{{ old('exchange_rate', $record?->exchange_rate) }}" min="0.000001"
               placeholder="1 USD = ? TRY">
        <small class="text-muted">{{ __('finance.exchange_rate_hint') }}</small>
    </div>

    @unless($singleTreasury)
    <div class="mb-2">
        <label class="form-label">{{ __('finance.treasury_account') }} *</label>
        <select name="account_id" class="form-select" required>
            @foreach($treasuryAccounts as $a)
            <option value="{{ $a->id }}" @selected(old('account_id', $record?->account_id ?? ($defaultTreasuryId ?? null)) == $a->id)>{{ $a->name }} ({{ $a->currency }})</option>
            @endforeach
        </select>
    </div>
    @else
    <input type="hidden" name="account_id" value="{{ $treasuryAccounts->first()->id }}">
    @endunless

    @if(!empty($orders))
    <div class="mb-2">
        <label class="form-label">{{ __('finance.linked_order') }}</label>
        <select name="order_id" class="form-select">
            <option value="">— {{ __('finance.linked_order_hint') }}</option>
            @foreach($orders as $o)
            <option value="{{ $o->id }}" @selected(old('order_id', ($record?->reference_type === \App\Models\Order::class ? $record?->reference_id : null) ?? request('order_id')) == $o->id)>
                {{ $o->order_number }} · {{ $o->customer?->company_name ?? '—' }} ({{ $o->currency }})
            </option>
            @endforeach
        </select>
        <small class="text-muted">{{ __('finance.linked_order_note') }}</small>
    </div>
    @endif

    @if($compact)
    <details class="mb-2 small">
        <summary class="text-muted mb-2" style="cursor:pointer">{{ __('finance.optional_details') }}</summary>
        <div class="mb-2">
            <label class="form-label">{{ __('finance.vendor') }}</label>
            <input type="text" name="vendor" class="form-control form-control-sm" value="{{ old('vendor', $record?->vendor) }}" maxlength="200">
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="form-label">{{ __('finance.payment_method') }}</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">—</option>
                    @foreach(($paymentMethods ?? finance_categories()->paymentMethods()) as $pmKey => $pmLabel)
                    <option value="{{ $pmKey }}" @selected(old('payment_method', $record?->payment_method) === $pmKey)>{{ $pmLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">{{ __('finance.receipt_no') }}</label>
                <input type="text" name="receipt_no" class="form-control form-control-sm" value="{{ old('receipt_no', $record?->receipt_no) }}" maxlength="100">
            </div>
        </div>
        <div class="mb-0">
            <label class="form-label">{{ __('finance.notes') }}</label>
            <textarea name="notes" class="form-control form-control-sm" rows="2" maxlength="2000">{{ old('notes', $record?->notes) }}</textarea>
        </div>
    </details>
    @else
    <div class="mb-2">
        <label class="form-label">{{ __('finance.vendor') }}</label>
        <input type="text" name="vendor" class="form-control" value="{{ old('vendor', $record?->vendor) }}" maxlength="200">
    </div>
    <div class="row g-2 mb-2">
        <div class="col-6">
            <label class="form-label">{{ __('finance.payment_method') }}</label>
            <select name="payment_method" class="form-select">
                <option value="">—</option>
                @foreach(($paymentMethods ?? finance_categories()->paymentMethods()) as $pmKey => $pmLabel)
                <option value="{{ $pmKey }}" @selected(old('payment_method', $record?->payment_method) === $pmKey)>{{ $pmLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6">
            <label class="form-label">{{ __('finance.receipt_no') }}</label>
            <input type="text" name="receipt_no" class="form-control" value="{{ old('receipt_no', $record?->receipt_no) }}" maxlength="100">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">{{ __('finance.notes') }}</label>
        <textarea name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes', $record?->notes) }}</textarea>
    </div>
    @endif
</div>

@once
@push('scripts')
<script>
(function () {
    const baseCurrency = @json(registry()->defaultCurrency()?->code ?? 'TRY');
    const toggleExchange = () => {
        const sel = document.getElementById('ie-currency');
        const wrap = document.getElementById('ie-exchange-wrap');
        if (!sel || !wrap) return;
        wrap.style.display = sel.value !== baseCurrency ? '' : 'none';
    };
    document.getElementById('ie-currency')?.addEventListener('change', toggleExchange);
    toggleExchange();
})();
</script>
@endpush
@endonce
