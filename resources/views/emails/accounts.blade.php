@extends('layouts.app')
@section('title', __('emails.accounts'))
@section('content')
@include('partials.page-header', ['title' => __('emails.accounts')])

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('emails.index') }}" class="btn btn-ghost-secondary btn-sm"><i class="ti ti-arrow-left"></i> {{ __('emails.back_to_inbox') }}</a>
    @if(can_access('emails.view'))
    <a href="{{ route('emails.signatures') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-writing-sign me-1"></i> {{ __('emails.signatures') }}</a>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(!$imapAvailable)
<div class="alert alert-warning">{{ __('emails.imap_missing') }}</div>
@endif

@if($errors->has('imap'))
<div class="alert alert-danger">{{ $errors->first('imap') }}</div>
@endif

@if(can_access('emails.create'))
<div class="card mb-3">
    <div class="card-header"><h3 class="card-title mb-0">{{ __('emails.new_account') }}</h3></div>
    <div class="card-body">
        @include('emails.partials.account-form', [
            'formAction' => route('emails.accounts.store'),
        ])
    </div>
</div>
@endif

<div class="card">
    <div class="list-group list-group-flush">
        @forelse($accounts as $a)
        <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
            <div class="min-w-0">
                <div class="fw-semibold">{{ $a->name }}</div>
                <div class="text-muted small">{{ $a->email }} · {{ __('emails.providers.'.$a->provider) }}</div>
                @if($a->imap_host)<div class="text-muted small">{{ __('emails.incoming_server') }}: {{ $a->imap_host }}:{{ $a->imap_port }}</div>@endif
                @if($a->smtp_host)<div class="text-muted small">{{ __('emails.outgoing_server') }}: {{ $a->smtp_host }}:{{ $a->smtp_port }}</div>@endif
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                @if($a->is_default)<span class="badge bg-primary-lt">{{ __('emails.is_default') }}</span>@endif
                @if(Route::has('emails.accounts.edit') && (can_access('emails.edit') || can_access('emails.create')))
                <a href="{{ route('emails.accounts.edit', $a) }}" class="btn btn-sm btn-ghost-primary" title="{{ __('app.edit') }}">
                    <i class="ti ti-edit"></i>
                </a>
                @endif
                @if(Route::has('emails.accounts.destroy') && (can_access('emails.delete') || can_access('emails.create')))
                <form action="{{ route('emails.accounts.destroy', $a) }}" method="POST"
                      onsubmit="return confirm(@json(__('emails.delete_account_confirm')))">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-ghost-danger" title="{{ __('emails.delete_account') }}">
                        <i class="ti ti-trash"></i>
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="list-group-item text-muted">{{ __('emails.no_accounts') }}</div>
        @endforelse
    </div>
</div>
@endsection
