@php
    $currentPeriod = request('period', 'month');
    $currentDate = request('date', now()->format('Y-m-d'));
@endphp
<form method="GET" class="ef-finance-filter">
    @foreach(request()->except(['period', 'date', 'from', 'to', 'page']) as $key => $value)
        @if(is_string($value) || is_numeric($value))
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1">{{ __('finance.report_period') }}</label>
            <div class="btn-group w-100" role="group">
                @foreach(['day' => __('finance.period_day'), 'week' => __('finance.period_week'), 'month' => __('finance.period_month'), 'year' => __('finance.period_year')] as $key => $label)
                <input type="radio" class="btn-check" name="period" id="period-{{ $key }}-{{ $uid ?? 'main' }}" value="{{ $key }}" @checked($currentPeriod === $key) onchange="this.form.submit()">
                <label class="btn btn-sm btn-outline-primary" for="period-{{ $key }}-{{ $uid ?? 'main' }}">{{ $label }}</label>
                @endforeach
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">{{ __('finance.period_date') }}</label>
            <input type="date" name="date" class="form-control form-control-sm" value="{{ $currentDate }}" onchange="this.form.submit()">
        </div>
        @if($showExtra ?? true)
        <div class="col-md-2">
            <label class="form-label small mb-1">{{ __('finance.entry_type') }}</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">{{ __('finance.all_types') }}</option>
                <option value="expense" @selected(request('type')==='expense')>{{ __('finance.type_expense') }}</option>
                <option value="income" @selected(request('type')==='income')>{{ __('finance.type_income') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">{{ __('app.search') }}</label>
            <div class="input-group input-group-sm">
                <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('finance.filter_search') }}">
                <button type="submit" class="btn btn-secondary">{{ __('app.filter') }}</button>
            </div>
        </div>
        @endif
    </div>
    @if(isset($periodMeta))
    <div class="text-muted small mt-2">
        <i class="ti ti-calendar me-1"></i>{{ __('finance.period_range') }}: <strong>{{ $periodMeta['label'] }}</strong>
        · {{ $summary['total_count'] ?? 0 }} {{ __('finance.record_count') }}
    </div>
    @endif
</form>
