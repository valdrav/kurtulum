@extends('layouts.app')
@section('title', __('app.tasks'))
@section('content')
@include('partials.page-header', ['title' => __('app.tasks')])

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('tasks.index', ['view' => 'kanban']) }}" class="btn btn-outline-primary btn-sm"><i class="ti ti-layout-kanban"></i> Kanban</a>
    <a href="{{ route('calendar.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-calendar"></i> Takvim</a>
    <button type="button" class="btn btn-primary btn-sm ms-auto" data-bs-toggle="collapse" data-bs-target="#taskForm"><i class="ti ti-plus"></i> Yeni görev</button>
</div>

<div class="collapse mb-3" id="taskForm">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('tasks.store') }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-6"><input type="text" name="title" class="form-control" placeholder="Başlık *" required></div>
                    <div class="col-md-3">
                        <select name="priority" class="form-select">
                            <option value="low">Düşük</option>
                            <option value="medium" selected>Orta</option>
                            <option value="high">Yüksek</option>
                            <option value="urgent">Acil</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="pending" selected>Bekliyor</option>
                            <option value="in_progress">Devam ediyor</option>
                        </select>
                    </div>
                    <div class="col-md-4"><input type="date" name="due_date" class="form-control"></div>
                    <div class="col-md-4"><input type="datetime-local" name="reminder_at" class="form-control"></div>
                    <div class="col-md-4">
                        <select name="assigned_to" class="form-select">
                            <option value="">Atanmadı</option>
                            @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12"><textarea name="description" class="form-control" rows="2" placeholder="Açıklama"></textarea></div>
                    <div class="col-md-6"><input type="text" name="labels" class="form-control" placeholder="Etiketler (lojistik, ödeme, ...)"></div>
                    <div class="col-md-3"><input type="number" name="estimated_hours" class="form-control" placeholder="Tahmini saat" min="1"></div>
                    <div class="col-md-3"><button type="submit" class="btn btn-primary w-100">{{ __('app.save') }}</button></div>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-6 col-md-2">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">Durum</option>
            @foreach(['pending','in_progress','completed','cancelled'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-2">
        <select name="priority" class="form-select" onchange="this.form.submit()">
            <option value="">Öncelik</option>
            @foreach(['urgent','high','medium','low'] as $p)
            <option value="{{ $p }}" @selected(request('priority')===$p)>{{ $p }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="assigned_to" class="form-select" onchange="this.form.submit()">
            <option value="">Tüm atananlar</option>
            @foreach($users as $u)<option value="{{ $u->id }}" @selected(request('assigned_to')==$u->id)>{{ $u->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <a href="{{ route('tasks.index', ['mine' => 1]) }}" class="btn btn-outline-secondary w-100">Benim görevlerim</a>
    </div>
</form>

<div class="card d-md-none">
    @forelse($tasks as $t)
    @php $prog = $t->checklistProgress(); @endphp
    <div class="card-body border-bottom">
        <div class="d-flex justify-content-between">
            <div class="fw-semibold">{{ $t->title }}</div>
            <span class="badge priority-{{ $t->priority }}">{{ $t->priority }}</span>
        </div>
        <div class="text-muted small mt-1">{{ $t->due_date?->format('d.m.Y') ?? '—' }} · {{ $t->assignee?->name ?? 'Atanmadı' }}</div>
        @if($prog['total'] > 0)
        <div class="progress progress-sm mt-2"><div class="progress-bar" style="width: {{ $prog['total'] ? ($prog['done']/$prog['total']*100) : 0 }}%"></div></div>
        @endif
        @if($t->labels)<div class="mt-1">@foreach($t->labels as $lb)<span class="badge bg-secondary-lt me-1">{{ $lb }}</span>@endforeach</div>@endif
    </div>
    @empty
    <div class="card-body text-muted">{{ __('app.no_records') }}</div>
    @endforelse
</div>

<div class="card hide-mobile">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern">
            <thead><tr><th>Başlık</th><th>Öncelik</th><th>Durum</th><th>Termin</th><th>Atanan</th><th>İlerleme</th></tr></thead>
            <tbody>
                @forelse($tasks as $t)
                @php $prog = $t->checklistProgress(); @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $t->title }}</div>
                        @if($t->labels)<small>@foreach($t->labels as $lb)<span class="badge bg-secondary-lt me-1">{{ $lb }}</span>@endforeach</small>@endif
                    </td>
                    <td><span class="badge priority-{{ $t->priority }}">{{ $t->priority }}</span></td>
                    <td>{{ $t->status }}</td>
                    <td>{{ $t->due_date?->format('d.m.Y') ?? '-' }}</td>
                    <td>{{ $t->assignee?->name ?? '-' }}</td>
                    <td>@if($prog['total'] > 0){{ $prog['done'] }}/{{ $prog['total'] }}@else—@endif</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-muted">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $tasks->withQueryString()->links() }}</div>
@endsection
