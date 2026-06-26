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
                    <div class="col-md-6"><label class="form-label">{{ __('tasks.title') }} *</label><input type="text" name="title" class="form-control" required value="{{ old('title') }}"></div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('tasks.priority') }}</label>
                        <select name="priority" class="form-select">
                            @foreach(['low','medium','high','urgent'] as $p)
                            <option value="{{ $p }}" @selected($p==='medium')>{{ task_priority_label($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('app.status') }}</label>
                        <select name="status" class="form-select">
                            @foreach(['pending','in_progress'] as $s)
                            <option value="{{ $s }}" @selected($s==='pending')>{{ task_status_label($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('tasks.description') }}</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="{{ __('tasks.description_hint') }}">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-md-4"><label class="form-label">{{ __('tasks.due_date') }}</label><input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}"></div>
                    <div class="col-md-4"><label class="form-label">{{ __('tasks.reminder') }}</label><input type="datetime-local" name="reminder_at" class="form-control" value="{{ old('reminder_at') }}"></div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('tasks.assigned_to') }}</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">—</option>
                            @foreach($users as $u)<option value="{{ $u->id }}" @selected(old('assigned_to') == $u->id)>{{ $u->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">{{ __('tasks.labels') }}</label><input type="text" name="labels" class="form-control" placeholder="lojistik, ödeme" value="{{ old('labels') }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('tasks.estimated_hours') }}</label><input type="number" name="estimated_hours" class="form-control" min="1" value="{{ old('estimated_hours') }}"></div>
                    <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100">{{ __('app.save') }}</button></div>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-6 col-md-2">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('app.status') }}</option>
            @foreach(['pending','in_progress','completed','cancelled'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ task_status_label($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-2">
        <select name="priority" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('tasks.priority') }}</option>
            @foreach(['urgent','high','medium','low'] as $p)
            <option value="{{ $p }}" @selected(request('priority')===$p)>{{ task_priority_label($p) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="assigned_to" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('tasks.all_assignees') }}</option>
            @foreach($users as $u)<option value="{{ $u->id }}" @selected(request('assigned_to')==$u->id)>{{ $u->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <a href="{{ route('tasks.index', ['mine' => 1]) }}" class="btn btn-outline-secondary w-100">{{ __('tasks.my_tasks') }}</a>
    </div>
</form>

<div class="card d-md-none">
    @forelse($tasks as $t)
    @php $prog = $t->checklistProgress(); @endphp
    <div class="card-body border-bottom">
        <div class="d-flex justify-content-between">
            <div class="fw-semibold">{{ $t->title }}</div>
            <span class="badge priority-{{ $t->priority }}">{{ task_priority_label($t->priority) }}</span>
        </div>
        @if($t->description)<div class="text-muted small mt-1">{{ Str::limit($t->description, 120) }}</div>@endif
        <div class="text-muted small mt-1">{{ $t->due_date?->format('d.m.Y') ?? '—' }} · {{ $t->assignee?->name ?? __('tasks.unassigned') }}</div>
        @if(can_access('tasks.edit'))
        <button type="button" class="btn btn-sm btn-ghost-primary mt-2" data-bs-toggle="modal" data-bs-target="#taskEdit{{ $t->id }}">{{ __('app.edit') }}</button>
        @endif
    </div>
    @empty
    <div class="card-body text-muted">{{ __('app.no_records') }}</div>
    @endforelse
</div>

<div class="card hide-mobile">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern">
            <thead><tr><th>{{ __('tasks.title') }}</th><th>{{ __('tasks.description') }}</th><th>{{ __('tasks.priority') }}</th><th>{{ __('app.status') }}</th><th>{{ __('tasks.due_date') }}</th><th>{{ __('tasks.assigned_to') }}</th><th></th></tr></thead>
            <tbody>
                @forelse($tasks as $t)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $t->title }}</div>
                        @if($t->labels)<small>@foreach($t->labels as $lb)<span class="badge bg-secondary-lt me-1">{{ $lb }}</span>@endforeach</small>@endif
                    </td>
                    <td class="small text-muted" style="max-width:16rem">{{ Str::limit($t->description, 80) ?: '—' }}</td>
                    <td><span class="badge priority-{{ $t->priority }}">{{ task_priority_label($t->priority) }}</span></td>
                    <td>{{ task_status_label($t->status) }}</td>
                    <td>{{ $t->due_date?->format('d.m.Y') ?? '-' }}</td>
                    <td>{{ $t->assignee?->name ?? '-' }}</td>
                    <td>
                        @if(can_access('tasks.edit'))
                        <button type="button" class="btn btn-sm btn-ghost-primary" data-bs-toggle="modal" data-bs-target="#taskEdit{{ $t->id }}"><i class="ti ti-edit"></i></button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-muted">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($tasks as $t)
<div class="modal fade" id="taskEdit{{ $t->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('tasks.update', $t) }}">@csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">{{ __('tasks.edit') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">{{ __('tasks.title') }} *</label><input type="text" name="title" class="form-control" value="{{ $t->title }}" required></div>
                    <div class="mb-3"><label class="form-label">{{ __('tasks.description') }}</label><textarea name="description" class="form-control" rows="4">{{ $t->description }}</textarea></div>
                    <div class="row g-2">
                        <div class="col-md-4"><label class="form-label">{{ __('tasks.priority') }}</label><select name="priority" class="form-select">@foreach(['low','medium','high','urgent'] as $p)<option value="{{ $p }}" @selected($t->priority===$p)>{{ task_priority_label($p) }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">{{ __('app.status') }}</label><select name="status" class="form-select">@foreach(['pending','in_progress','completed','cancelled'] as $s)<option value="{{ $s }}" @selected($t->status===$s)>{{ task_status_label($s) }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">{{ __('tasks.due_date') }}</label><input type="date" name="due_date" class="form-control" value="{{ $t->due_date?->format('Y-m-d') }}"></div>
                        <div class="col-md-6"><label class="form-label">{{ __('tasks.assigned_to') }}</label><select name="assigned_to" class="form-select"><option value="">—</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected($t->assigned_to==$u->id)>{{ $u->name }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">{{ __('tasks.labels') }}</label><input type="text" name="labels" class="form-control" value="{{ $t->labels ? implode(', ', $t->labels) : '' }}"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">{{ __('app.save') }}</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<div class="mt-3">{{ $tasks->withQueryString()->links() }}</div>
@endsection
