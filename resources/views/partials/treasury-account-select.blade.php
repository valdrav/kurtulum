@php
    $treasuryAccounts = $treasuryAccounts ?? company_treasury()->accounts();
    $defaultTreasury = $defaultTreasury ?? company_treasury()->defaultAccount();
    $selectedId = old('treasury_account_id', $selected ?? $defaultTreasury->id);
    $singleTreasury = $treasuryAccounts->count() === 1;
@endphp

@if($singleTreasury)
<input type="hidden" name="treasury_account_id" value="{{ $treasuryAccounts->first()->id }}">
@else
<div class="mb-3">
    <label class="form-label">{{ __('finance.bank_account') }}</label>
    <select name="treasury_account_id" class="form-select" required>
        @foreach($treasuryAccounts as $ta)
        <option value="{{ $ta->id }}" @selected((string) $selectedId === (string) $ta->id)>{{ $ta->name }} ({{ $ta->currency }})</option>
        @endforeach
    </select>
</div>
@endif
