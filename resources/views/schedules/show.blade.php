@extends('layouts.app')
@section('title', $schedule->displayTitle())

@section('content')
@include('partials.page-header', [
    'title' => $schedule->displayTitle(),
    'subtitle' => $schedule->week_start->format('d.m.Y').' — '.$schedule->week_end->format('d.m.Y'),
    'backRoute' => route('schedules.index', ['year' => $schedule->year, 'month' => $schedule->month]),
])

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('schedules.export', $schedule) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-printer me-1"></i>{{ __('schedules.export_pdf') }}
    </a>
    <a href="{{ route('schedules.edit', $schedule) }}" class="btn btn-outline-primary btn-sm">
        <i class="ti ti-settings me-1"></i>{{ __('schedules.edit_meta') }}
    </a>
</div>

<form method="POST" action="{{ route('schedules.update', $schedule) }}" id="schedule-form">
    @csrf @method('PUT')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">{{ __('schedules.entries') }}</h3>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-schedule-row">{{ __('schedules.add_row') }}</button>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-modern mb-0" id="schedule-table">
                <thead>
                    <tr>
                        @foreach($columns as $key => $col)
                        <th style="min-width: {{ $col['width'] ?? 'auto' }}">{{ $col['label'] }}</th>
                        @endforeach
                        <th class="ef-table-actions"></th>
                    </tr>
                </thead>
                <tbody id="schedule-rows">
                    @forelse($schedule->entries as $i => $entry)
                    @include('schedules._row', ['index' => $i, 'entry' => $entry, 'columns' => $columns])
                    @empty
                    @include('schedules._row', ['index' => 0, 'entry' => null, 'columns' => $columns])
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
        </div>
    </div>
</form>

<template id="schedule-row-template">
    @include('schedules._row', ['index' => '__INDEX__', 'entry' => null, 'columns' => $columns])
</template>

@push('scripts')
<script>
(function () {
    const tbody = document.getElementById('schedule-rows');
    const tpl = document.getElementById('schedule-row-template');
    let rowIndex = tbody.querySelectorAll('tr').length;

    document.getElementById('add-schedule-row')?.addEventListener('click', function () {
        const html = tpl.innerHTML.replace(/__INDEX__/g, String(rowIndex++));
        tbody.insertAdjacentHTML('beforeend', html);
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-remove-row]');
        if (!btn) return;
        const rows = tbody.querySelectorAll('tr');
        if (rows.length <= 1) {
            btn.closest('tr').querySelectorAll('input').forEach(i => i.value = '');
            return;
        }
        btn.closest('tr').remove();
    });
})();
</script>
@endpush
@endsection
