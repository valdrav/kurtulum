@extends('layouts.app')
@section('title', __('emails.compose'))
@section('content')
@include('partials.page-header', ['title' => __('emails.compose')])

@if($accounts->isEmpty())
<div class="alert alert-info">
    {{ __('emails.inbox_empty') }}
    <a href="{{ route('emails.accounts') }}">{{ __('emails.inbox_empty_link') }}</a>
</div>
@else
@php
    $accountUuids = $accounts->pluck('uuid', 'id');
@endphp
<div class="row g-3" x-data="emailCompose(@js($signatureMap), @js($accountUuids), '{{ (string) $defaultAccountId }}', @js($linkType ?? ''), @js($linkId ?? ''))">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('emails.send') }}" @submit="prepareSubmit">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emails.from_account') }}</label>
                            <select name="email_account_id" class="form-select" required x-model="accountId" @change="onAccountChange()">
                                @foreach($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->name }} ({{ $a->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <label class="form-check mb-2">
                                <input type="hidden" name="include_signature" value="0">
                                <input type="checkbox" name="include_signature" value="1" class="form-check-input"
                                       x-model="includeSignature">
                                <span class="form-check-label">{{ __('emails.include_signature') }}</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('emails.to') }}</label>
                            <input type="text" name="to_emails" class="form-control" placeholder="ornek@firma.com"
                                   value="{{ old('to_emails') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('emails.subject_label') }}</label>
                            <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('emails.message') }}</label>
                            <div class="email-compose-toolbar btn-group btn-group-sm mb-2 flex-wrap">
                                <button type="button" class="btn btn-outline-secondary" @click.prevent="format('bold')"><i class="ti ti-bold"></i></button>
                                <button type="button" class="btn btn-outline-secondary" @click.prevent="format('italic')"><i class="ti ti-italic"></i></button>
                                <button type="button" class="btn btn-outline-secondary" @click.prevent="format('underline')"><i class="ti ti-underline"></i></button>
                                <button type="button" class="btn btn-outline-secondary" @click.prevent="insertSignature()">
                                    <i class="ti ti-writing-sign me-1"></i>{{ __('emails.insert_signature') }}
                                </button>
                            </div>
                            <div class="email-compose-editor" contenteditable="true" x-ref="editor" @input="syncBody()"></div>
                            <input type="hidden" name="body_html" x-ref="bodyHtml">
                        </div>
                        <div class="col-12">
                            <div class="text-muted small mb-1">{{ __('emails.preview_with_signature') }}</div>
                            <div class="email-compose-preview email-body-frame">
                                <div x-html="previewHtml()"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Kayıt bağlantısı (isteğe bağlı)</label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <select class="form-select" x-model="linkType" @change="linkId = ''">
                                        <option value="">—</option>
                                        <option value="order">{{ __('app.orders') }}</option>
                                        <option value="shipment">{{ __('app.shipments') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-8" x-show="linkType === 'order'">
                                    <select class="form-select" x-model="linkId">
                                        <option value="">Sipariş seçin</option>
                                        @foreach($orders as $o)
                                        <option value="{{ $o->id }}">{{ $o->order_number }} — {{ $o->customer?->company_name ?? '—' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8" x-show="linkType === 'shipment'" x-cloak>
                                    <select class="form-select" x-model="linkId">
                                        <option value="">Sevkiyat seçin</option>
                                        @foreach($shipments as $s)
                                        <option value="{{ $s->id }}">{{ $s->shipment_number }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" name="emailable_type" :value="emailableType()">
                            <input type="hidden" name="emailable_id" :value="linkId || ''">
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary"><i class="ti ti-send me-1"></i>{{ __('emails.send') }}</button>
                            <a href="{{ route('emails.index') }}" class="btn btn-link">{{ __('emails.back_to_inbox') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="ti ti-writing-sign me-2"></i>{{ __('emails.signature') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small">{{ __('emails.signature_compose_hint') }}</p>
                <div class="email-compose-editor email-compose-editor-sm mb-2" contenteditable="true"
                     x-ref="sigEditor" @input="onSignatureEdit()"></div>
                <label class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" x-model="signatureAuto" @change="syncSignatureAuto()">
                    <span class="form-check-label">{{ __('emails.signature_auto') }}</span>
                </label>
                <button type="button" class="btn btn-primary btn-sm w-100" @click="saveSignature()" :disabled="saving">
                    <i class="ti ti-device-floppy me-1"></i>{{ __('emails.save_signature') }}
                </button>
                <div class="text-success small mt-2" x-show="saved" x-cloak>{{ __('messages.saved') }}</div>
                <a href="{{ route('emails.signatures') }}" class="btn btn-link btn-sm w-100 mt-2">{{ __('emails.manage_signatures') }}</a>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@if(!$accounts->isEmpty())
@push('scripts')
<script>
function emailCompose(signatureMap, accountUuids, defaultAccountId, linkType, linkId) {
    return {
        accountId: String(defaultAccountId || Object.keys(signatureMap)[0] || ''),
        accountUuids,
        signatureMap,
        linkType: linkType || '',
        linkId: linkId ? String(linkId) : '',
        includeSignature: true,
        signatureAuto: true,
        saving: false,
        saved: false,
        init() {
            this.$nextTick(() => {
                if (this.$refs.editor && !this.$refs.editor.innerHTML.trim()) {
                    this.$refs.editor.innerHTML = '<p><br></p>';
                }
                this.onAccountChange();
            });
        },
        currentSignature() {
            return this.signatureMap[this.accountId]?.signature_html || '';
        },
        emailableType() {
            if (this.linkType === 'order') return @js(\App\Models\Order::class);
            if (this.linkType === 'shipment') return @js(\App\Models\Shipment::class);
            return '';
        },
        onAccountChange() {
            const row = this.signatureMap[this.accountId];
            this.includeSignature = row ? !!row.signature_auto : true;
            this.signatureAuto = row ? !!row.signature_auto : true;
            if (this.$refs.sigEditor) {
                this.$refs.sigEditor.innerHTML = this.currentSignature() || '<p><br></p>';
            }
            this.syncBody();
        },
        onSignatureEdit() {
            if (this.signatureMap[this.accountId] && this.$refs.sigEditor) {
                this.signatureMap[this.accountId].signature_html = this.$refs.sigEditor.innerHTML;
            }
        },
        syncSignatureAuto() {
            this.includeSignature = this.signatureAuto;
            if (this.signatureMap[this.accountId]) {
                this.signatureMap[this.accountId].signature_auto = this.signatureAuto;
            }
        },
        syncBody() {
            if (this.$refs.bodyHtml && this.$refs.editor) {
                this.$refs.bodyHtml.value = this.$refs.editor.innerHTML;
            }
        },
        format(cmd) {
            this.$refs.editor?.focus();
            document.execCommand(cmd, false, null);
            this.syncBody();
        },
        insertSignature() {
            const sig = this.currentSignature();
            if (!sig) return;
            this.$refs.editor?.focus();
            const sep = this.$refs.editor.innerText.trim() ? '<br><br><span style="color:#94a3b8">--</span><br>' : '';
            document.execCommand('insertHTML', false, sep + '<div class="ef-email-signature">' + sig + '</div>');
            this.syncBody();
        },
        previewHtml() {
            let body = this.$refs.editor?.innerHTML || '';
            const sig = this.currentSignature();
            if (this.includeSignature && sig && !body.includes(sig)) {
                const sep = body.replace(/<[^>]+>/g, '').trim() ? '<br><br><span style="color:#94a3b8">--</span><br>' : '';
                body += sep + '<div class="ef-email-signature">' + sig + '</div>';
            }
            return body || '<span class="text-muted">' + @json(__('emails.message_placeholder')) + '</span>';
        },
        prepareSubmit(e) {
            this.syncBody();
            const text = (this.$refs.bodyHtml?.value || '').replace(/<[^>]+>/g, '').trim();
            if (!text && !this.currentSignature()) {
                e.preventDefault();
                alert(@json(__('emails.body_required')));
            }
        },
        saveSignature() {
            const uuid = this.accountUuids[this.accountId];
            if (!uuid) return;
            this.saving = true;
            this.saved = false;
            const sig = this.$refs.sigEditor?.innerHTML || '';
            fetch(@json(url('emails/accounts')) + '/' + uuid + '/signature', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    signature_html: sig,
                    signature_auto: this.signatureAuto,
                }),
            })
            .then(r => {
                if (!r.ok) throw new Error('save failed');
                return r.json();
            })
            .then(() => {
                this.signatureMap[this.accountId].signature_html = sig;
                this.signatureMap[this.accountId].signature_auto = this.signatureAuto;
                this.includeSignature = this.signatureAuto;
                this.saved = true;
                setTimeout(() => this.saved = false, 2500);
            })
            .catch(() => alert(@json(__('emails.signature_save_failed'))))
            .finally(() => this.saving = false);
        },
    };
}
</script>
@endpush
@endif
