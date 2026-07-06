@extends('layouts.app')
@section('title', __('schedules.title'))

@section('content')
@include('partials.page-header', [
    'title' => __('schedules.title'),
    'subtitle' => __('schedules.subtitle'),
    'createRoute' => route('schedules.create', ['year' => $year, 'month' => $month]),
])

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label">Yıl</label>
                <select name="year" class="form-select">
                    @foreach($years as $y)<option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Ay</label>
                <select name="month" class="form-select">
                    <option value="">Tümü</option>
                    @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($month == $m)>{{ str_pad((string)$m, 2, '0', STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-12 col-md-auto"><button class="btn btn-outline-primary">{{ __('app.filter') }}</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern">
            <thead>
                <tr>
                    <th>Yıl</th>
                    <th>Ay</th>
                    <th>{{ __('schedules.week') }}</th>
                    <th>{{ __('schedules.week_range') }}</th>
                    <th>Başlık</th>
                    <th>Satır</th>
                    <th class="ef-table-actions"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($programs as $program)
                <tr>
                    <td>{{ $program->year }}</td>
                    <td>{{ str_pad((string)$program->month, 2, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $program->week_number }}</td>
                    <td>{{ $program->week_start->format('d.m.Y') }} — {{ $program->week_end->format('d.m.Y') }}</td>
                    <td>{{ $program->displayTitle() }}</td>
                    <td>{{ $program->entries_count }}</td>
                    <td class="ef-table-actions text-nowrap text-end">
                        <a href="{{ route('schedules.show', $program) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i></a>
                        <a href="{{ route('schedules.export', $program) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="ti ti-printer"></i></a>
                        <form method="POST" action="{{ route('schedules.destroy', $program) }}" class="d-inline" data-confirm="{{ __('app.confirm_delete') }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button></form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('schedules.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
