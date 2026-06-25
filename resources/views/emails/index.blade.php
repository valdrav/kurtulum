@extends('layouts.app')
@section('title', __('app.emails'))
@section('content')
@include('partials.page-header', ['title' => __('emails.inbox')])

@if(!empty($syncMessage))
<div class="alert alert-success py-2 small">{{ $syncMessage }}</div>
@endif

<div class="d-flex flex-wrap gap-2 mb-3">
    @if(can_access('emails.create'))
    <a href="{{ route('emails.compose') }}" class="btn btn-primary btn-sm"><i class="ti ti-pencil me-1"></i> {{ __('emails.compose') }}</a>
    <a href="{{ route('emails.accounts') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-settings me-1"></i> {{ __('emails.accounts') }}</a>
    <a href="{{ route('emails.signatures') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-writing-sign me-1"></i> {{ __('emails.signatures') }}</a>
    @if(can_access('documents.view'))
    <a href="{{ route('documents.tools.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-file-settings me-1"></i> {{ __('documents.tools.title') }}</a>
    @endif
    @if($imapAvailable)
    <form method="POST" action="{{ route('emails.sync') }}" class="d-inline">@csrf
        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="ti ti-refresh me-1"></i> {{ __('emails.sync') }}</button>
    </form>
    @else
    <span class="badge bg-yellow-lt align-self-center">{{ __('emails.imap_missing') }}</span>
    @endif
    @endif
</div>

@if($accounts->isEmpty())
<div class="alert alert-info">
    {{ __('emails.inbox_empty') }} <a href="{{ route('emails.accounts') }}">{{ __('emails.inbox_empty_link') }}</a>
</div>
@else
<div class="card mb-3 email-filters-card">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('emails.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4 col-12">
                <label class="form-label small mb-1">{{ __('app.search') }}</label>
                <input type="search" name="q" class="form-control form-control-sm" value="{{ request('q') }}"
                       placeholder="{{ __('emails.search_placeholder') }}">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label small mb-1">{{ __('emails.filter_account') }}</label>
                <select name="account" class="form-select form-select-sm">
                    <option value="">{{ __('emails.filter_all_accounts') }}</option>
                    @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" @selected(request('account') == $acc->id)>{{ \Illuminate\Support\Str::limit($acc->email, 28) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label small mb-1">{{ __('emails.filter_type') }}</label>
                <select name="direction" class="form-select form-select-sm">
                    <option value="">{{ __('emails.filter_all_types') }}</option>
                    <option value="inbound" @selected(request('direction') === 'inbound')>{{ __('emails.direction_inbound') }}</option>
                    <option value="outbound" @selected(request('direction') === 'outbound')>{{ __('emails.direction_outbound') }}</option>
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label small mb-1">{{ __('emails.date_from') }}</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label small mb-1">{{ __('emails.date_to') }}</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-filter me-1"></i>{{ __('app.filter') }}</button>
                <a href="{{ route('emails.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('emails.clear_filters') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="email-folder-tabs mb-3">
    <a href="{{ route('emails.index', request()->except('folder')) }}" class="btn btn-sm @if(!request('folder')) btn-primary @else btn-outline-secondary @endif">{{ __('emails.folder_all') }}</a>
    <a href="{{ route('emails.index', array_merge(request()->except('folder'), ['folder' => 'unread'])) }}" class="btn btn-sm @if(request('folder')==='unread') btn-primary @else btn-outline-secondary @endif">{{ __('emails.folder_unread') }}</a>
    <a href="{{ route('emails.index', array_merge(request()->except('folder'), ['folder' => 'starred'])) }}" class="btn btn-sm @if(request('folder')==='starred') btn-primary @else btn-outline-secondary @endif">{{ __('emails.folder_starred') }}</a>
</div>
@endif

<div class="card email-inbox-card">
    <div class="list-group list-group-flush">
        @forelse($emails as $email)
        <a href="{{ route('emails.show', $email) }}" class="email-list-item list-group-item list-group-item-action @if(!$email->is_read) unread @endif">
            <div class="email-list-top">
                <div class="d-flex align-items-center gap-1 min-w-0 flex-grow-1">
                    @if($email->is_starred)
                    <span class="email-list-badges"><i class="ti ti-star-filled text-yellow"></i></span>
                    @endif
                    @if($email->direction === 'outbound')
                    <span class="badge bg-azure-lt badge-sm flex-shrink-0">{{ __('emails.direction_outbound') }}</span>
                    @endif
                    <span class="email-list-from">{{ $email->from_name ?? $email->from_email }}</span>
                </div>
                <time class="email-list-date">{{ ($email->received_at ?? $email->sent_at)?->format('d.m H:i') }}</time>
            </div>
            <div class="email-list-subject">{{ $email->subject ?: __('emails.no_subject') }}</div>
            @if($preview = $email->previewText(90))
            <div class="email-list-preview text-muted small">{{ $preview }}</div>
            @endif
        </a>
        @empty
        <div class="list-group-item text-muted py-4 text-center">{{ __('app.no_records') }}</div>
        @endforelse
    </div>
</div>
<div class="mt-3 overflow-auto">{{ $emails->links() }}</div>
@endsection
