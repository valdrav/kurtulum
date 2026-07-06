@extends('layouts.app')
@section('title', __('schedules.title'))

@section('content')
@include('partials.page-header', [
    'title' => __('schedules.title'),
    'subtitle' => __('schedules.subtitle'),
    'createRoute' => route('schedules.create', ['year' => $year, 'month' => now()->month]),
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
            <div class="col-12 col-md-auto"><button class="btn btn-outline-primary">{{ __('app.filter') }}</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern">
            <thead>
                <tr>
                    <th>Ay</th>
                    <th>Dönem</th>
                    <th>Başlık</th>
                    <th>Kayıt</th>
                    <th class="ef-table-actions"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($programs as $program)
                <tr>
                    <td class="fw-semibold">{{ $program->displayTitle() }}</td>
                    <td>{{ $program->week_start->format('d.m.Y') }} — {{ $program->week_end->format('d.m.Y') }}</td>
                    <td>{{ $program->title ?? '—' }}</td>
                    <td>{{ $program->entries_count }}</td>
                    <td class="ef-table-actions text-nowrap text-end">
                        <a href="{{ route('schedules.show', $program) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i></a>
                        <a href="{{ route('schedules.export.form', $program) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-printer"></i></a>
                        <form method="POST" action="{{ route('schedules.destroy', $program) }}" class="d-inline" data-confirm="{{ __('app.confirm_delete') }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button></form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('schedules.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    <p class="text-muted small mb-2">{{ __('schedules.create_month_hint') }}</p>
    <div class="d-flex flex-wrap gap-2">
        @for($m = 1; $m <= 12; $m++)
        @php
            $exists = $programs->firstWhere('month', $m);
        @endphp
        @if(! $exists)
        @php
            $months = [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
        @endphp
        <a href="{{ route('schedules.create', ['year' => $year, 'month' => $m]) }}" class="btn btn-sm btn-outline-primary">
            {{ $months[$m] }}
        </a>
        @endif
        @endfor
    </div>
</div>
@endsection
