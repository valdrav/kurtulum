@extends('layouts.app')
@section('title', $email->subject)
@section('content')
<div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    <a href="{{ route('emails.index') }}" class="btn btn-ghost-secondary btn-sm"><i class="ti ti-arrow-left"></i> {{ __('emails.back_to_inbox') }}</a>
    @if($email->direction === 'outbound')
    <span class="badge bg-azure-lt">{{ __('emails.direction_outbound') }}</span>
    @else
    <span class="badge bg-green-lt">{{ __('emails.direction_inbound') }}</span>
    @endif
</div>

<div class="card email-show-card">
    <div class="card-header">
        <h3 class="card-title mb-1">{{ $email->subject ?: __('emails.no_subject') }}</h3>
        <div class="text-muted small">
            <strong>{{ $email->from_name ?? $email->from_email }}</strong>
            @if($email->from_name && $email->from_email)
            <span class="d-block d-sm-inline text-muted">&lt;{{ $email->from_email }}&gt;</span>
            @endif
        </div>
        <div class="text-muted small mt-1">
            {{ ($email->received_at ?? $email->sent_at)?->format('d.m.Y H:i') }}
            @if($email->emailAccount)
            · {{ $email->emailAccount->email }}
            @endif
        </div>
    </div>
    @if($email->attachments->isNotEmpty())
    <div class="card-body border-top border-bottom py-3 bg-light-lt">
        <div class="small text-muted mb-2 fw-semibold">
            <i class="ti ti-paperclip me-1"></i>{{ __('emails.attachments') }} ({{ $email->attachments->count() }})
        </div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($email->attachments as $attachment)
            <a href="{{ route('emails.attachments.download', $attachment) }}" class="email-attachment-chip">
                <i class="ti {{ $attachment->iconClass() }}"></i>
                <span class="email-attachment-name">{{ $attachment->filename }}</span>
                <span class="email-attachment-size">{{ $attachment->humanSize() }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif
    <div class="card-body p-2 p-sm-3">
        @php $html = $email->sanitizedHtml(); @endphp
        @if($html)
        <div class="email-body-frame">
            <div class="email-body-content">{!! $html !!}</div>
        </div>
        @elseif(trim((string) $email->body_text) !== '')
        <div class="email-body-frame">
            <pre class="email-body-text">{{ $email->body_text }}</pre>
        </div>
        @else
        <div class="alert alert-secondary mb-0">
            {{ __('emails.no_body') }}
            @if(can_access('emails.create'))
            <span class="d-block small mt-1">{{ __('emails.no_body_hint') }}</span>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
