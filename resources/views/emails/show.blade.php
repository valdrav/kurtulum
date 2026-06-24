@extends('layouts.app')
@section('title', $email->subject)
@section('content')
<div class="mb-3">
    <a href="{{ route('emails.index') }}" class="btn btn-ghost-secondary btn-sm"><i class="ti ti-arrow-left"></i> Gelen kutusu</a>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-1">{{ $email->subject }}</h3>
        <div class="text-muted small">
            {{ $email->from_name ?? $email->from_email }}
            · {{ ($email->received_at ?? $email->sent_at)?->format('d.m.Y H:i') }}
        </div>
    </div>
    <div class="card-body">
        @if($email->body_html)
        <div class="email-body">{!! $email->body_html !!}</div>
        @else
        <pre class="mb-0" style="white-space:pre-wrap;font-family:inherit">{{ $email->body_text }}</pre>
        @endif
    </div>
</div>
@endsection
