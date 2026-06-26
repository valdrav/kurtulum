@extends('layouts.app')
@section('title', __('tasks.kanban'))
@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="page-title mb-0">{{ __('tasks.kanban') }}</h2>
    <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('tasks.list_view') }}</a>
</div>

<div class="kanban-board">
    @foreach(['pending', 'in_progress', 'completed'] as $key)
    <div class="kanban-col">
        <div class="fw-semibold mb-2">{{ task_status_label($key) }} <span class="badge bg-secondary-lt">{{ $columns[$key]->count() }}</span></div>
        @foreach($columns[$key] as $task)
        @php $prog = $task->checklistProgress(); @endphp
        <div class="kanban-card">
            <div class="d-flex justify-content-between gap-1 mb-1">
                <span class="fw-semibold small">{{ $task->title }}</span>
                <span class="badge priority-{{ $task->priority }}">{{ task_priority_label($task->priority) }}</span>
            </div>
            <div class="text-muted small">{{ $task->assignee?->name ?? __('tasks.unassigned') }}</div>
            @if($task->due_date)<div class="text-muted small"><i class="ti ti-calendar"></i> {{ $task->due_date->format('d.m') }}</div>@endif
            @if($prog['total'] > 0)
            <div class="progress progress-sm mt-2"><div class="progress-bar" style="width: {{ ($prog['done']/$prog['total']*100) }}%"></div></div>
            @endif
            <form method="POST" action="{{ route('tasks.update', $task) }}" class="mt-2">
                @csrf @method('PUT')
                <input type="hidden" name="title" value="{{ $task->title }}">
                <input type="hidden" name="priority" value="{{ $task->priority }}">
                <input type="hidden" name="description" value="{{ $task->description }}">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(['pending','in_progress','completed','cancelled'] as $s)
                    <option value="{{ $s }}" @selected($task->status===$s)>{{ task_status_label($s) }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        @endforeach
    </div>
    @endforeach
</div>
@endsection
