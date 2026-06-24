@extends('layouts.settings')
@section('settings-title', __('settings.mail'))

@section('settings-content')
<form method="POST" action="{{ route('settings.mail.update') }}">
    @csrf @method('PUT')

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">{{ __('settings.mail_outgoing') }}</h3>
            <div class="card-subtitle text-muted">{{ __('settings.mail_outgoing_desc') }}</div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.mail_mailer') }}</label>
                    <select name="mail_mailer" class="form-select">
                        <option value="smtp" @selected(old('mail_mailer', $settings['mail_mailer']) === 'smtp')>{{ __('settings.mail_mailer_smtp') }}</option>
                        <option value="log" @selected(old('mail_mailer', $settings['mail_mailer']) === 'log')>{{ __('settings.mail_mailer_log') }}</option>
                    </select>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">{{ __('settings.mail_host') }}</label>
                    <input type="text" name="mail_host" class="form-control" value="{{ old('mail_host', $settings['mail_host']) }}" placeholder="smtp.gmail.com">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('settings.mail_port') }}</label>
                    <input type="number" name="mail_port" class="form-control" value="{{ old('mail_port', $settings['mail_port']) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.mail_username') }}</label>
                    <input type="text" name="mail_username" class="form-control" value="{{ old('mail_username', $settings['mail_username']) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.mail_password') }}</label>
                    <input type="password" name="mail_password" class="form-control" placeholder="••••••••">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.mail_encryption') }}</label>
                    <select name="mail_encryption" class="form-select">
                        <option value="tls" @selected(old('mail_encryption', $settings['mail_encryption']) === 'tls')>TLS</option>
                        <option value="ssl" @selected(old('mail_encryption', $settings['mail_encryption']) === 'ssl')>SSL</option>
                        <option value="null" @selected(old('mail_encryption', $settings['mail_encryption']) === 'null')>{{ __('settings.mail_encryption_none') }}</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.mail_from_address') }}</label>
                    <input type="email" name="mail_from_address" class="form-control" value="{{ old('mail_from_address', $settings['mail_from_address']) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.mail_from_name') }}</label>
                    <input type="text" name="mail_from_name" class="form-control" value="{{ old('mail_from_name', $settings['mail_from_name']) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">{{ __('settings.mail_incoming') }}</h3>
            <div class="card-subtitle text-muted">{{ __('settings.mail_incoming_desc') }}</div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.mail_imap_host') }}</label>
                    <input type="text" name="mail_imap_host" class="form-control" value="{{ old('mail_imap_host', $settings['mail_imap_host']) }}" placeholder="imap.gmail.com">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('settings.mail_imap_port') }}</label>
                    <input type="number" name="mail_imap_port" class="form-control" value="{{ old('mail_imap_port', $settings['mail_imap_port'] ?? 993) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('settings.mail_imap_encryption') }}</label>
                    <select name="mail_imap_encryption" class="form-select">
                        <option value="ssl" @selected(old('mail_imap_encryption', $settings['mail_imap_encryption'] ?? 'ssl') === 'ssl')>SSL</option>
                        <option value="tls" @selected(old('mail_imap_encryption', $settings['mail_imap_encryption'] ?? '') === 'tls')>TLS</option>
                        <option value="null" @selected(old('mail_imap_encryption', $settings['mail_imap_encryption'] ?? '') === 'null')>{{ __('settings.mail_encryption_none') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
</form>
@endsection
