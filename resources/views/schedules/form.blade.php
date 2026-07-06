@extends('layouts.app')
@section('title', $program->exists ? $program->displayTitle() : __('schedules.new_month'))

@section('content')
@include('partials.page-header', [
    'title' => $program->exists ? $program->displayTitle() : __('schedules.new_month'),
    'backRoute' => route('schedules.index', ['year' => $program->year]),
])

<form method="POST" action="{{ $program->exists ? route('schedules.update', $program) : route('schedules.store') }}">
    @csrf
    @if($program->exists) @method('PUT') @endif
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2"><label class="form-label">{{ __('schedules.year') }}</label><input type="number" name="year" class="form-control" value="{{ old('year', $program->year) }}" required></div>
                <div class="col-md-2"><label class="form-label">{{ __('schedules.month') }}</label><input type="number" min="1" max="12" name="month" class="form-control" value="{{ old('month', $program->month) }}" required></div>
                <div class="col-md-8"><label class="form-label">{{ __('schedules.title_label') }}</label><input type="text" name="title" class="form-control" value="{{ old('title', $program->title) }}" placeholder="{{ __('schedules.optional') }}"></div>
                <div class="col-12"><label class="form-label">{{ __('schedules.notes') }}</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $program->notes) }}</textarea></div>
            </div>
            @if($program->week_start && $program->week_end)
            <p class="text-muted small mt-2 mb-0">{{ __('schedules.period') }}: {{ $program->week_start->format('d.m.Y') }} — {{ $program->week_end->format('d.m.Y') }}</p>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
        @if($program->exists)
        <a href="{{ route('schedules.show', $program) }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a>
        @else
        <a href="{{ route('schedules.index', ['year' => $program->year]) }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a>
        @endif
    </div>
</form>
@endsection
