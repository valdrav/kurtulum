@extends('layouts.app')
@section('title', __('emails.accounts'))
@section('content')
@include('partials.page-header', ['title' => __('emails.accounts')])

@if(!$imapAvailable)
<div class="alert alert-warning">{{ __('emails.imap_missing') }}</div>
@endif

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title mb-0">{{ __('emails.new_account') }}</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('emails.accounts.store') }}" x-data="{ provider: 'microsoft365' }">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('emails.account_name') }}</label>
                    <input type="text" name="name" class="form-control" placeholder="{{ __('emails.account_name_hint') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('emails.email') }}</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('emails.provider') }}</label>
                    <select name="provider" class="form-select" x-model="provider">
                        @foreach(['microsoft365','google','yandex','custom'] as $p)
                        <option value="{{ $p }}">{{ __('emails.providers.'.$p) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('emails.username') }}</label>
                    <input type="text" name="smtp_username" class="form-control" placeholder="{{ __('emails.username_hint') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('emails.password') }}</label>
                    <input type="password" name="smtp_password" class="form-control" autocomplete="new-password">
                </div>

                <template x-if="provider === 'custom'">
                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-12"><h4 class="h5 mb-0">{{ __('emails.incoming_server') }}</h4></div>
                            <div class="col-md-4"><input type="text" name="imap_host" class="form-control" placeholder="{{ __('emails.imap_host') }}"></div>
                            <div class="col-md-2"><input type="number" name="imap_port" class="form-control" placeholder="993" value="993"></div>
                            <div class="col-md-2">
                                <select name="imap_encryption" class="form-select">
                                    <option value="ssl">SSL</option>
                                    <option value="tls">TLS</option>
                                </select>
                            </div>
                            <div class="col-12 mt-2"><h4 class="h5 mb-0">{{ __('emails.outgoing_server') }}</h4></div>
                            <div class="col-md-4"><input type="text" name="smtp_host" class="form-control" placeholder="{{ __('emails.smtp_host') }}"></div>
                            <div class="col-md-2"><input type="number" name="smtp_port" class="form-control" placeholder="587" value="587"></div>
                            <div class="col-md-2">
                                <select name="smtp_encryption" class="form-select">
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="col-12">
                    <label class="form-check">
                        <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default">
                        <span class="form-check-label">{{ __('emails.is_default') }}</span>
                    </label>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
                    <a href="{{ route('emails.index') }}" class="btn btn-link">{{ __('emails.back_to_inbox') }}</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="list-group list-group-flush">
        @forelse($accounts as $a)
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">{{ $a->name }}</div>
                <div class="text-muted small">{{ $a->email }} · {{ __('emails.providers.'.$a->provider) }}</div>
                @if($a->imap_host)<div class="text-muted small">{{ __('emails.incoming_server') }}: {{ $a->imap_host }}:{{ $a->imap_port }}</div>@endif
                @if($a->smtp_host)<div class="text-muted small">{{ __('emails.outgoing_server') }}: {{ $a->smtp_host }}:{{ $a->smtp_port }}</div>@endif
            </div>
            @if($a->is_default)<span class="badge bg-primary-lt">{{ __('emails.is_default') }}</span>@endif
        </div>
        @empty
        <div class="list-group-item text-muted">{{ __('emails.no_accounts') }}</div>
        @endforelse
    </div>
</div>
@endsection
