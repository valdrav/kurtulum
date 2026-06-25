@extends('layouts.settings')
@section('settings-title', __('app.audit_log'))

@section('settings-content')
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
