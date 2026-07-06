@extends('layouts.app')
@section('title', $program->exists ? $program->displayTitle() : __('schedules.new_week'))

@section('content')
@include('partials.page-header', [
    'title' => $program->exists ? __('schedules.edit_meta') : __('schedules.new_week'),
    'backRoute' => route('schedules.index', ['year' => $program->year, 'month' => $program->month]),
])

<form method="POST" action="{{ $program->exists ? route('schedules.update', $program) : route('schedules.store') }}">
    @csrf
    @if($program->exists) @method('PUT') @endif
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2"><label class="form-label">Yıl</label><input type="number" name="year" class="form-control" value="{{ old('year', $program->year) }}" required></div>
                <div class="col-md-2"><label class="form-label">Ay</label><input type="number" min="1" max="12" name="month" class="form-control" value="{{ old('month', $program->month) }}" required></div>
                <div class="col-md-2"><label class="form-label">{{ __('schedules.week') }}</label><input type="number" min="1" max="53" name="week_number" class="form-control" value="{{ old('week_number', $program->week_number) }}" required></div>
                <div class="col-md-3"><label class="form-label">Başlangıç</label><input type="date" name="week_start" class="form-control" value="{{ old('week_start', $program->week_start?->format('Y-m-d')) }}" required></div>
                <div class="col-md-3"><label class="form-label">Bitiş</label><input type="date" name="week_end" class="form-control" value="{{ old('week_end', $program->week_end?->format('Y-m-d')) }}" required></div>
                <div class="col-md-6"><label class="form-label">Başlık</label><input type="text" name="title" class="form-control" value="{{ old('title', $program->title) }}" placeholder="Opsiyonel"></div>
                <div class="col-12"><label class="form-label">Not</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $program->notes) }}</textarea></div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
        @if($program->exists)
        <a href="{{ route('schedules.show', $program) }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a>
        @else
        <a href="{{ route('schedules.index') }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a>
        @endif
    </div>
</form>
@endsection
