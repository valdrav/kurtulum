@extends('layouts.settings')
@section('settings-title', __('app.audit_log'))

@section('settings-content')
<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-2">
        <label class="form-label small">{{ __('audit.user') }}</label>
        <select name="user_id" class="form-select form-select-sm">
            <option value="">Tümü</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">{{ __('audit.action') }}</label>
        <select name="event" class="form-select form-select-sm">
            <option value="">Tümü</option>
            @foreach(__('audit.events') as $key => $label)
            <option value="{{ $key }}" @selected(request('event') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">{{ __('audit.subject') }}</label>
        <select name="subject_type" class="form-select form-select-sm">
            <option value="">Tümü</option>
            @foreach(__('audit.subjects') as $type => $label)
            <option value="{{ $type }}" @selected(request('subject_type') === $type)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">{{ __('app.search') }}</label>
        <input type="search" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Açıklama, detay...">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">{{ __('app.filter') }}</button>
        @if(request()->hasAny(['user_id', 'event', 'subject_type', 'search']))
        <a href="{{ route('settings.audit-log') }}" class="btn btn-ghost-secondary btn-sm">Temizle</a>
        @endif
    </div>
</form>

<div class="card audit-log-card">
    <div class="table-responsive">
        <table class="table table-vcenter table-modern card-table table-sm audit-log-table">
            <thead>
                <tr>
                    <th class="text-nowrap">{{ __('app.date') }}</th>
                    <th>{{ __('audit.user') }}</th>
                    <th>{{ __('audit.action') }}</th>
                    <th>{{ __('audit.subject') }}</th>
                    <th>{{ __('audit.details') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-nowrap small align-top">{{ $log->created_at->format('d.m.Y H:i') }}</td>
                    <td class="small align-top">{{ $log->causer?->name ?? __('audit.system') }}</td>
                    <td class="align-top">
                        <span class="badge bg-blue-lt">{{ activity_event_label($log->event ?? $log->description) }}</span>
                    </td>
                    <td class="small align-top">
                        <span class="fw-semibold">{{ activity_subject_label($log->subject_type) }}</span>
                        @if($log->subject_id)<span class="text-muted"> #{{ $log->subject_id }}</span>@endif
                    </td>
                    <td class="small align-top audit-log-details">{!! activity_changes_html($log) !!}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())<div class="card-footer">{{ $logs->links() }}</div>@endif
</div>
@endsection
