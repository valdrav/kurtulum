@php
    $isEdit = isset($account);
    $providerDefault = $isEdit ? $account->provider : 'plesk';
    $emailDefault = $isEdit ? $account->email : '';
@endphp

<form method="POST" action="{{ $formAction }}"
      x-data="{
          provider: '{{ old('provider', $providerDefault) }}',
          email: '{{ old('email', $emailDefault) }}',
          mailHost() {
              const d = this.email.includes('@') ? this.email.split('@')[1] : 'alanadiniz.com';
              return 'mail.' + d;
          }
      }">
    @csrf
    @if(($formMethod ?? 'POST') !== 'POST')
        @method($formMethod)
    @endif
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">{{ __('emails.account_name') }}</label>
            <input type="text" name="name" class="form-control" placeholder="{{ __('emails.account_name_hint') }}"
                   value="{{ old('name', $isEdit ? $account->name : '') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('emails.email') }}</label>
            <input type="email" name="email" class="form-control" x-model="email"
                   value="{{ old('email', $emailDefault) }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('emails.provider') }}</label>
            <select name="provider" class="form-select" x-model="provider">
                @foreach(['plesk','microsoft365','google','yandex','custom'] as $p)
                <option value="{{ $p }}" @selected(old('provider', $providerDefault) === $p)>{{ __('emails.providers.'.$p) }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-12" x-show="provider === 'plesk'" x-cloak>
            <div class="alert alert-info mb-0 py-2">{{ __('emails.plesk_hint') }}</div>
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('emails.username') }}</label>
            <input type="text" name="smtp_username" class="form-control" placeholder="{{ __('emails.username_hint') }}"
                   value="{{ old('smtp_username', $isEdit ? $account->smtpUsername() : '') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('emails.password') }}</label>
            <input type="password" name="smtp_password" class="form-control" autocomplete="new-password"
                   @unless($isEdit) required @endunless
                   placeholder="{{ $isEdit ? __('emails.password_keep') : '' }}">
            @if($isEdit)
            <div class="form-hint">{{ __('emails.password_keep_hint') }}</div>
            @endif
        </div>

        <template x-if="provider === 'plesk' || provider === 'custom'">
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-12"><h4 class="h5 mb-0">{{ __('emails.incoming_server') }}</h4></div>
                    <div class="col-md-4">
                        @if($isEdit)
                        <input type="text" name="imap_host" class="form-control" placeholder="{{ __('emails.imap_host') }}"
                               value="{{ old('imap_host', $account->imap_host) }}">
                        @else
                        <input type="text" name="imap_host" class="form-control" placeholder="{{ __('emails.imap_host') }}"
                               x-bind:value="provider === 'plesk' && !{{ json_encode((bool) old('imap_host')) }} ? mailHost() : $el.value"
                               value="{{ old('imap_host') }}">
                        @endif
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="imap_port" class="form-control" placeholder="993"
                               value="{{ old('imap_port', $isEdit ? $account->imap_port : 993) }}">
                    </div>
                    <div class="col-md-2">
                        <select name="imap_encryption" class="form-select">
                            <option value="ssl" @selected(old('imap_encryption', $isEdit ? $account->imap_encryption : 'ssl') === 'ssl')>SSL</option>
                            <option value="tls" @selected(old('imap_encryption', $isEdit ? $account->imap_encryption : '') === 'tls')>TLS</option>
                        </select>
                    </div>
                    <div class="col-12 mt-2"><h4 class="h5 mb-0">{{ __('emails.outgoing_server') }}</h4></div>
                    <div class="col-md-4">
                        @if($isEdit)
                        <input type="text" name="smtp_host" class="form-control" placeholder="{{ __('emails.smtp_host') }}"
                               value="{{ old('smtp_host', $account->smtp_host) }}">
                        @else
                        <input type="text" name="smtp_host" class="form-control" placeholder="{{ __('emails.smtp_host') }}"
                               x-bind:value="provider === 'plesk' && !{{ json_encode((bool) old('smtp_host')) }} ? mailHost() : $el.value"
                               value="{{ old('smtp_host') }}">
                        @endif
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="smtp_port" class="form-control" placeholder="587"
                               value="{{ old('smtp_port', $isEdit ? $account->smtp_port : 587) }}">
                    </div>
                    <div class="col-md-2">
                        <select name="smtp_encryption" class="form-select">
                            <option value="tls" @selected(old('smtp_encryption', $isEdit ? $account->smtp_encryption : 'tls') === 'tls')>TLS</option>
                            <option value="ssl" @selected(old('smtp_encryption', $isEdit ? $account->smtp_encryption : '') === 'ssl')>SSL</option>
                        </select>
                    </div>
                </div>
            </div>
        </template>

        <div class="col-12">
            <label class="form-check">
                <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default"
                       @checked(old('is_default', $isEdit ? $account->is_default : false))>
                <span class="form-check-label">{{ __('emails.is_default') }}</span>
            </label>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">{{ $submitLabel ?? __('app.save') }}</button>
            <a href="{{ route('emails.accounts') }}" class="btn btn-link">{{ __('emails.back_to_accounts') }}</a>
        </div>
    </div>
</form>
