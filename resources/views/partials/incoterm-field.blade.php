@props(['name' => 'incoterm', 'selected' => null])

<div x-data="{ open: false, code: '{{ old($name, $selected ?? '') }}' }">
    <div class="d-flex align-items-center gap-2 mb-1">
        <label class="form-label mb-0">{{ __('orders.delivery_type') }}</label>
        <button type="button" class="btn btn-ghost-secondary btn-sm px-1" @click="open = !open" title="{{ __('incoterms.help_title') }}">
            <i class="ti ti-info-circle"></i>
        </button>
    </div>
    <select name="{{ $name }}" class="form-select" x-model="code">
        <option value="">—</option>
        @foreach(config('ticari.incoterms') as $i)
        <option value="{{ $i }}">{{ incoterm_label($i) }}</option>
        @endforeach
    </select>
    <div class="form-hint">{{ __('orders.delivery_type_hint') }}</div>
    <div class="alert alert-info mt-2 small" x-show="open" x-cloak>
        <strong>{{ __('incoterms.help_title') }}</strong>
        <p class="mb-2">{{ __('incoterms.help_intro') }}</p>
        @foreach(config('ticari.incoterms') as $i)
        <div x-show="code === '{{ $i }}'" x-cloak>
            <strong>{{ $i }}</strong> — {{ incoterm_description($i) }}
        </div>
        @endforeach
    </div>
</div>
