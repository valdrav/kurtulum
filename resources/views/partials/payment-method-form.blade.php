<div x-data="paymentForm()" x-init="init()">
    <div class="mb-3">
        <label class="form-label fw-semibold">{{ __('app.amount') }}</label>
        <input type="number" step="0.01" name="amount" class="form-control amount-input-lg" placeholder="0,00" required @input="updatePreview">
    </div>

    <div class="mb-3" x-show="currencies.length">
        <label class="form-label fw-semibold">{{ __('app.currency') }}</label>
        <div class="currency-pills">
            <template x-for="c in currencies" :key="c">
                <label class="currency-pill">
                    <input type="radio" name="currency" :value="c" :checked="c === selectedCurrency" @change="selectedCurrency = c">
                    <span x-text="c"></span>
                </label>
            </template>
        </div>
        <small class="text-muted d-block mt-1" x-show="feePreview" x-text="feePreview"></small>
    </div>

    <div class="mb-3" x-show="selectedCurrency && selectedCurrency !== baseCurrency" x-cloak>
        <label class="form-label fw-semibold">{{ __('finance.exchange_rate') }}</label>
        <input type="number" step="0.000001" name="exchange_rate" class="form-control" min="0.000001"
               :placeholder="'1 ' + selectedCurrency + ' = ? ' + baseCurrency">
        <small class="text-muted">{{ __('finance.exchange_rate_hint') }}</small>
    </div>

    <div class="mb-2">
        <label class="form-label">{{ __('extensions.payment_method') }}</label>
        <select name="payment_method_id" class="form-select" x-model="methodId" @change="loadFields()" required>
            <option value="">{{ __('extensions.select_payment_method') }}</option>
            @foreach($paymentMethods as $m)
            <option value="{{ $m->id }}">{{ $m->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-2" x-show="requiresReference">
        <label class="form-label">{{ __('extensions.reference') }} *</label>
        <input type="text" name="reference" class="form-control" :required="requiresReference">
    </div>

    <template x-for="field in fields" :key="field.name">
        <div class="mb-2">
            <label class="form-label" x-text="field.label + (field.required ? ' *' : '')"></label>
            <template x-if="field.type === 'select'">
                <select :name="'method_data[' + field.name + ']'" class="form-select" :required="field.required">
                    <template x-for="opt in (field.options || [])" :key="opt">
                        <option :value="opt" x-text="opt"></option>
                    </template>
                </select>
            </template>
            <template x-if="field.type !== 'select'">
                <input :type="field.type || 'text'" :name="'method_data[' + field.name + ']'" class="form-control" :required="field.required">
            </template>
        </div>
    </template>

    <div class="mb-2">
        <label class="form-label">{{ $dateLabel ?? __('app.date') }}</label>
        <input type="date" name="{{ $dateField ?? 'payment_date' }}" class="form-control" value="{{ date('Y-m-d') }}" required>
    </div>
</div>

@once
@push('scripts')
<script>
function paymentForm() {
    return {
        methodId: '',
        fields: [],
        currencies: @json(registry()->currencyCodes()),
        selectedCurrency: @json(registry()->currencyCodes()[0] ?? 'TRY'),
        baseCurrency: @json(registry()->defaultCurrency()?->code ?? 'TRY'),
        requiresReference: false,
        feePreview: '',
        init() {
            if (this.currencies.length === 0) {
                this.currencies = ['TRY', 'USD', 'EUR'];
                this.selectedCurrency = 'TRY';
            }
        },
        updatePreview() {},
        async loadFields() {
            if (!this.methodId) return;
            const res = await fetch(`{{ url('settings/payment-methods') }}/${this.methodId}/fields`);
            const data = await res.json();
            this.fields = data.fields || [];
            this.currencies = data.currencies?.length ? data.currencies : @json(registry()->currencyCodes());
            if (this.currencies.length && !this.currencies.includes(this.selectedCurrency)) {
                this.selectedCurrency = this.currencies[0];
            }
            this.requiresReference = data.method.requires_reference;
            if (data.method.fee_type !== 'none') {
                this.feePreview = data.method.fee_type === 'percent'
                    ? `Komisyon: %${data.method.fee_amount}`
                    : `Komisyon: ${data.method.fee_amount}`;
            } else {
                this.feePreview = '';
            }
        }
    }
}
</script>
@endpush
@endonce
