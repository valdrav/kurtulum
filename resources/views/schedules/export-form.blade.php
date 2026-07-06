@extends('layouts.app')
@section('title', __('schedules.export_pdf'))

@section('content')
@include('partials.page-header', [
    'title' => __('schedules.export_pdf'),
    'subtitle' => $schedule->displayTitle(),
    'backRoute' => route('schedules.show', $schedule),
])

<form method="GET" action="{{ route('schedules.export', $schedule) }}" target="_blank" class="card">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">{{ __('schedules.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ old('date_from', $defaultFrom) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('schedules.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ old('date_to', $defaultTo) }}" required>
            </div>
        </div>

        <div class="mb-2 fw-semibold">{{ __('schedules.export_columns') }}</div>
        <p class="text-muted small">{{ __('schedules.export_columns_hint') }}</p>
        <div class="row g-2 mb-3">
            @foreach($columns as $key => $col)
            <div class="col-md-4 col-lg-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="columns[]" value="{{ $key }}" id="col-{{ $key }}" checked>
                    <label class="form-check-label" for="col-{{ $key }}">{{ $col['label'] }}</label>
                </div>
            </div>
            @endforeach
        </div>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all-cols">{{ __('schedules.select_all_columns') }}</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-all-cols">{{ __('schedules.clear_all_columns') }}</button>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary"><i class="ti ti-printer me-1"></i>{{ __('schedules.export_pdf') }}</button>
    </div>
</form>

@push('scripts')
<script>
document.getElementById('select-all-cols')?.addEventListener('click', () => {
    document.querySelectorAll('input[name="columns[]"]').forEach(c => c.checked = true);
});
document.getElementById('clear-all-cols')?.addEventListener('click', () => {
    document.querySelectorAll('input[name="columns[]"]').forEach(c => c.checked = false);
});
</script>
@endpush
@endsection
