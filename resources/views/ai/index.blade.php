@extends('layouts.app')
@section('title', __('app.ai_assistant'))

@push('styles')
<style>
.ai-chat-layout { min-height: calc(100vh - 12rem); }
.ai-sidebar { display: flex; flex-direction: column; gap: .75rem; }
.ai-conv-list { max-height: 360px; overflow-y: auto; }
.ai-conv-item { cursor: pointer; border: none; background: transparent; width: 100%; text-align: left; }
.ai-conv-item.active { background: var(--tblr-primary-lt, rgba(32,107,196,.08)); }
.ai-msg-user { background: var(--tblr-primary-lt, #e7f0ff); }
.ai-msg-assistant { background: var(--tblr-bg-surface-secondary, #f4f6fa); }
[data-bs-theme="dark"] .ai-msg-assistant { background: rgba(255,255,255,.06); }
.ai-chat-header { border-bottom: 1px solid var(--ef-border, var(--tblr-border-color)); }
.ai-tool-nav .list-group-item.active { font-weight: 600; }
[x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
@include('partials.page-header', ['title' => __('app.ai_assistant'), 'subtitle' => $configured ? __('ai.provider_label', ['provider' => $provider]) : null])

@if(!$configured)
<div class="alert alert-warning mb-3">{{ __('ai.not_configured') }}</div>
@endif

<div class="ai-chat-layout" x-data="aiChatApp()" x-cloak>
    <div class="row g-3 h-100">
        <div class="col-lg-3">
            <div class="ai-sidebar">
                <button type="button" class="btn btn-primary w-100" @click="newConversation()" :disabled="!configured">
                    <i class="ti ti-plus me-1"></i>{{ __('ai.new_chat') }}
                </button>

                <div class="card flex-grow-1">
                    <div class="card-header py-2">
                        <span class="fw-semibold small">{{ __('ai.conversations') }}</span>
                    </div>
                    <div class="list-group list-group-flush ai-conv-list">
                        <template x-for="conv in conversations" :key="conv.id">
                            <div class="list-group-item list-group-item-action p-2 d-flex align-items-center gap-2"
                                 :class="conv.id === activeId && 'active'">
                                <button type="button" class="ai-conv-item flex-grow-1 p-2 rounded" @click="loadConversation(conv.id)">
                                    <div class="text-truncate small fw-medium" x-text="conv.title || '{{ __('ai.new_chat') }}'"></div>
                                </button>
                                <button type="button" class="btn btn-sm btn-ghost-danger p-1" @click.stop="deleteConversation(conv.id)" :title="labels.delete">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </template>
                        <div x-show="conversations.length === 0" class="list-group-item text-muted small py-3 text-center">
                            {{ __('ai.no_conversations') }}
                        </div>
                    </div>
                </div>

                <div class="card ai-tool-nav">
                    <div class="card-header py-2"><span class="fw-semibold small">{{ __('ai.tools') }}</span></div>
                    <div class="list-group list-group-flush">
                        <button type="button" class="list-group-item list-group-item-action small" :class="panel === 'chat' && 'active'" @click="openChat()">
                            <i class="ti ti-message me-1"></i>{{ __('ai.chat_panel') }}
                        </button>
                        <button type="button" class="list-group-item list-group-item-action small" :class="panel === 'email' && 'active'" @click="openTool('email')">
                            <i class="ti ti-mail me-1"></i>{{ __('ai.generate_email') }}
                        </button>
                        <button type="button" class="list-group-item list-group-item-action small" :class="panel === 'translate' && 'active'" @click="openTool('translate')">
                            <i class="ti ti-language me-1"></i>{{ __('ai.translate') }}
                        </button>
                        <button type="button" class="list-group-item list-group-item-action small" :class="panel === 'summarize' && 'active'" @click="openTool('summarize')">
                            <i class="ti ti-file-text me-1"></i>{{ __('ai.summarize') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            {{-- Chat panel --}}
            <div class="card h-100" x-show="panel === 'chat'" style="min-height:520px">
                <div class="card-header ai-chat-header d-flex align-items-center justify-content-between py-3">
                    <div>
                        <h3 class="card-title mb-0" x-text="activeTitle || '{{ __('ai.new_chat') }}'"></h3>
                        <div class="text-muted small" x-show="configured">{{ __('ai.chat_privacy') }}</div>
                    </div>
                    <span class="badge bg-secondary-lt" x-show="loading">{{ __('ai.thinking') }}…</span>
                </div>
                <div class="card-body d-flex flex-column p-0" style="min-height:440px">
                    <div class="flex-grow-1 overflow-auto p-3" id="aiChatMessages" x-ref="messages">
                        <template x-for="(msg, idx) in messages" :key="idx">
                            <div class="mb-3" :class="msg.role === 'user' ? 'text-end' : ''">
                                <div class="d-inline-block p-3 rounded-3 small text-start shadow-sm" style="max-width:88%"
                                     :class="msg.role === 'user' ? 'ai-msg-user' : 'ai-msg-assistant'">
                                    <div class="text-muted mb-1" style="font-size:.7rem" x-text="msg.role === 'user' ? labels.you : labels.assistant"></div>
                                    <div style="white-space:pre-wrap" x-text="msg.content"></div>
                                </div>
                            </div>
                        </template>
                        <div x-show="messages.length === 0 && !loading" class="text-center py-5 px-3">
                            <div class="mb-3"><i class="ti ti-sparkles text-primary" style="font-size:2.5rem"></i></div>
                            <p class="text-muted mb-0">{{ __('ai.chat_intro') }}</p>
                        </div>
                    </div>
                    <div class="border-top p-3">
                        <form @submit.prevent="sendMessage()">
                            <div class="input-group">
                                <textarea x-ref="input" x-model="input" class="form-control" rows="2"
                                          :placeholder="labels.placeholder"
                                          :disabled="loading || !configured"
                                          @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"></textarea>
                                <button type="submit" class="btn btn-primary px-3" :disabled="loading || !configured || !input.trim()">
                                    <span x-show="!loading"><i class="ti ti-send"></i></span>
                                    <span x-show="loading" class="spinner-border spinner-border-sm"></span>
                                </button>
                            </div>
                            <div class="form-hint mt-1">{{ __('ai.enter_hint') }}</div>
                            <div x-show="error" class="text-danger small mt-2" x-text="error"></div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Tools panel --}}
            <div class="card" x-show="panel !== 'chat'">
                <div class="card-header">
                    <h3 class="card-title mb-0" x-text="toolTitles[panel] || ''"></h3>
                </div>
                <div class="card-body">
                    <textarea x-model="toolInput" class="form-control mb-3" rows="8" :placeholder="labels.toolPlaceholder"></textarea>
                    <button type="button" class="btn btn-primary" @click="runTool()" :disabled="toolLoading || !configured || !toolInput.trim()">
                        <span x-show="!toolLoading"><i class="ti ti-sparkles me-1"></i>{{ __('ai.run') }}</span>
                        <span x-show="toolLoading" class="spinner-border spinner-border-sm"></span>
                    </button>
                    <div x-show="toolResult" class="mt-4 p-3 ai-msg-assistant rounded-3 small" style="white-space:pre-wrap" x-text="toolResult"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="application/json" id="ai-chat-bootstrap">@json($chatConfig)</script>
<script>
document.addEventListener('alpine:init', () => {
    const bootstrap = JSON.parse(document.getElementById('ai-chat-bootstrap').textContent);

    Alpine.data('aiChatApp', () => ({
        configured: bootstrap.configured,
        chatUrl: bootstrap.chatUrl,
        csrf: bootstrap.csrf,
        labels: bootstrap.labels,
        toolUrls: bootstrap.toolUrls,
        toolTitles: bootstrap.toolTitles,
        conversations: bootstrap.conversations || [],
        activeId: null,
        activeTitle: '',
        messages: [],
        input: '',
        loading: false,
        error: '',
        panel: 'chat',
        toolInput: '',
        toolResult: '',
        toolLoading: false,

        init() {
            if (this.conversations.length > 0) {
                this.loadConversation(this.conversations[0].id);
            } else {
                this.newConversation();
            }
        },

        openChat() {
            this.panel = 'chat';
            this.$nextTick(() => this.focusInput());
        },

        openTool(name) {
            this.panel = name;
            this.toolResult = '';
        },

        newConversation() {
            this.activeId = null;
            this.activeTitle = this.labels.newChat;
            this.messages = [];
            this.input = '';
            this.error = '';
            this.panel = 'chat';
            this.$nextTick(() => this.focusInput());
        },

        focusInput() {
            if (this.$refs.input && this.configured) {
                this.$refs.input.focus();
            }
        },

        scrollMessages() {
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        conversationUrl(id) {
            return bootstrap.conversationUrlTemplate.replace('__UUID__', encodeURIComponent(id));
        },

        async loadConversation(id) {
            this.panel = 'chat';
            this.activeId = id;
            this.error = '';
            this.loading = true;

            try {
                const res = await fetch(this.conversationUrl(id), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) {
                    throw new Error(this.labels.loadFailed);
                }
                const data = await res.json();
                this.activeTitle = data.title || this.labels.newChat;
                this.messages = (data.messages || []).map(m => ({ role: m.role, content: m.content }));
                this.scrollMessages();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
                this.focusInput();
            }
        },

        async sendMessage() {
            const text = this.input.trim();
            if (!text || this.loading || !this.configured) return;

            this.loading = true;
            this.error = '';
            this.messages.push({ role: 'user', content: text });
            this.input = '';
            this.scrollMessages();

            try {
                const res = await fetch(this.chatUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ message: text, conversation_id: this.activeId }),
                });

                let data = {};
                try { data = await res.json(); } catch (_) {}

                if (!res.ok) {
                    this.messages.pop();
                    throw new Error(data.error || data.message || this.labels.sendFailed);
                }

                this.activeId = data.conversation_id;
                this.activeTitle = data.title || this.labels.newChat;
                this.messages.push({ role: 'assistant', content: data.reply });

                const existing = this.conversations.find(c => c.id === data.conversation_id);
                if (existing) {
                    existing.title = data.title;
                } else {
                    this.conversations.unshift({ id: data.conversation_id, title: data.title });
                }
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
                this.scrollMessages();
                this.focusInput();
            }
        },

        async deleteConversation(id) {
            if (!confirm(this.labels.deleteConfirm)) return;

            const res = await fetch(this.conversationUrl(id), {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!res.ok) {
                this.error = this.labels.deleteFailed;
                return;
            }

            this.conversations = this.conversations.filter(c => c.id !== id);
            if (this.activeId === id) {
                this.newConversation();
            }
        },

        async runTool() {
            if (!this.toolInput.trim() || this.toolLoading || !this.configured) return;

            this.toolLoading = true;
            this.toolResult = '';
            const url = this.toolUrls[this.panel];
            const body = this.panel === 'translate'
                ? { text: this.toolInput, from: bootstrap.locale, to: 'en' }
                : { context: this.toolInput, data: this.toolInput };

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                this.toolResult = data.result || this.labels.sendFailed;
            } catch (_) {
                this.toolResult = this.labels.sendFailed;
            } finally {
                this.toolLoading = false;
            }
        },
    }));
});
</script>
@endpush
