@extends('layouts.app')
@section('title', __('emails.signatures'))
@section('content')
@include('partials.page-header', ['title' => __('emails.signatures')])

<div class="mb-3">
    <a href="{{ route('emails.index') }}" class="btn btn-ghost-secondary btn-sm"><i class="ti ti-arrow-left"></i> {{ __('emails.back_to_inbox') }}</a>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($accounts->isEmpty())
<div class="alert alert-info">{{ __('emails.inbox_empty') }} <a href="{{ route('emails.accounts') }}">{{ __('emails.inbox_empty_link') }}</a></div>
@else
<div class="row g-3">
    @foreach($accounts as $account)
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">{{ $account->email }}</h3>
                <div class="card-subtitle text-muted small">{{ $account->name }}</div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('emails.accounts.signature.update', $account) }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">{{ __('emails.signature_html') }}</label>
                        <textarea name="signature_html" class="form-control font-monospace" rows="8"
                                  placeholder="{{ __('emails.signature_placeholder') }}">{{ old('signature_html', $account->signature_html) }}</textarea>
                        <div class="form-hint">{{ __('emails.signature_html_hint') }}</div>
                    </div>
                    <label class="form-check mb-3">
                        <input type="checkbox" name="signature_auto" value="1" class="form-check-input"
                               @checked(old('signature_auto', $account->signature_auto))>
                        <span class="form-check-label">{{ __('emails.signature_auto') }}</span>
                    </label>
                    @if($account->signature_html)
                    <div class="mb-3">
                        <div class="text-muted small mb-1">{{ __('emails.signature_preview') }}</div>
                        <div class="email-body-frame email-signature-preview">{!! $account->signature_html !!}</div>
                    </div>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-device-floppy me-1"></i>{{ __('emails.save_signature') }}</button>
                    <a href="{{ route('emails.accounts.edit', $account) }}" class="btn btn-link btn-sm">{{ __('emails.edit_account') }}</a>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
