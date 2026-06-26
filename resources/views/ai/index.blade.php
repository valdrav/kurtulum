@extends('layouts.app')
@section('title', __('app.ai_assistant'))
@section('content')
@include('partials.page-header', ['title' => __('app.ai_assistant'), 'subtitle' => $configured ? __('ai.provider_label', ['provider' => $provider]) : null])

@if(!$configured)
<div class="alert alert-warning">{{ __('ai.not_configured') }}</div>
@endif

<div class="row g-3" x-data="aiChatApp(@json([
    'chatUrl' => route('ai.chat'),
    'csrf' => csrf_token(),
    'configured' => $configured,
    'newChatLabel' => __('ai.new_chat'),
    'placeholder' => __('ai.chat_placeholder'),
    'sendLabel' => __('ai.send'),
    'deleteLabel' => __('ai.delete_conversation'),
    'deleteConfirm' => __('ai.delete_conversation_confirm'),
    'conversations' => $conversations->map(fn ($c) => ['id' => $c->id, 'title' => $c->title])->values(),
]))">
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold small">{{ __('ai.conversations') }}</span>
                <button type="button" class="btn btn-sm btn-primary" @click="newConversation()" :disabled="!configured">
                    <i class="ti ti-plus"></i>
                </button>
            </div>
            <div class="list-group list-group-flush" style="max-height:420px;overflow:auto">
                <template x-for="conv in conversations" :key="conv.id">
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-2"
                            :class="conv.id === activeId && 'active'" @click="loadConversation(conv.id)">
                        <span class="text-truncate small" x-text="conv.title"></span>
                        <span class="btn btn-sm btn-ghost-danger p-0" @click.stop="deleteConversation(conv.id)" title="{{ __('ai.delete_conversation') }}">
                            <i class="ti ti-trash"></i>
                        </span>
                    </button>
                </template>
                <div x-show="conversations.length === 0" class="list-group-item text-muted small">{{ __('ai.no_conversations') }}</div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header py-2"><span class="fw-semibold small">{{ __('ai.tools') }}</span></div>
            <div class="list-group list-group-flush">
                <button type="button" class="list-group-item list-group-item-action small" :class="tool==='email' && 'active'" @click="tool='email'">{{ __('ai.generate_email') }}</button>
                <button type="button" class="list-group-item list-group-item-action small" :class="tool==='translate' && 'active'" @click="tool='translate'">{{ __('ai.translate') }}</button>
                <button type="button" class="list-group-item list-group-item-action small" :class="tool==='summarize' && 'active'" @click="tool='summarize'">{{ __('ai.summarize') }}</button>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="card" x-show="tool === 'chat'" style="min-height:520px">
            <div class="card-body d-flex flex-column" style="min-height:500px">
                <div class="flex-grow-1 overflow-auto mb-3 pe-1" id="aiChatMessages">
                    <template x-for="(msg, idx) in messages" :key="idx">
                        <div class="mb-3" :class="msg.role === 'user' ? 'text-end' : ''">
                            <div class="d-inline-block p-3 rounded small text-start" style="max-width:85%"
                                 :class="msg.role === 'user' ? 'bg-primary-lt' : 'bg-light'">
                                <div class="text-muted mb-1" style="font-size:.7rem" x-text="msg.role === 'user' ? '{{ __('ai.you') }}' : '{{ __('ai.assistant') }}'"></div>
                                <div style="white-space:pre-wrap" x-text="msg.content"></div>
                            </div>
                        </div>
                    </template>
                    <div x-show="messages.length === 0" class="text-muted text-center py-5">{{ __('ai.chat_intro') }}</div>
                </div>
                <form @submit.prevent="sendMessage()" class="mt-auto">
                    <div class="input-group">
                        <textarea x-model="input" class="form-control" rows="2" :placeholder="placeholder" :disabled="loading || !configured"></textarea>
                        <button type="submit" class="btn btn-primary" :disabled="loading || !configured || !input.trim()">
                            <span x-show="!loading"><i class="ti ti-send"></i></span>
                            <span x-show="loading" class="spinner-border spinner-border-sm"></span>
                        </button>
                    </div>
                    <div x-show="error" class="text-danger small mt-2" x-text="error"></div>
                </form>
            </div>
        </div>

        <div class="card" x-show="tool !== 'chat'">
            <div class="card-body">
                <textarea x-model="toolInput" class="form-control mb-3" rows="6" placeholder="{{ __('ai.tool_placeholder') }}"></textarea>
                <button type="button" class="btn btn-primary" @click="runTool()" :disabled="toolLoading || !configured">
                    <i class="ti ti-sparkles"></i> {{ __('ai.run') }}
                </button>
                <div x-show="toolResult" class="mt-4 p-3 bg-light rounded small" style="white-space:pre-wrap" x-text="toolResult"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function aiChatApp(config) {
    return {
        configured: config.configured,
        chatUrl: config.chatUrl,
        csrf: config.csrf,
        placeholder: config.placeholder,
        conversations: config.conversations || [],
        activeId: null,
        messages: [],
        input: '',
        loading: false,
        error: '',
        tool: 'chat',
        toolInput: '',
        toolResult: '',
        toolLoading: false,
        newConversation() {
            this.activeId = null;
            this.messages = [];
            this.input = '';
            this.error = '';
            this.tool = 'chat';
        },
        async loadConversation(id) {
            this.tool = 'chat';
            this.activeId = id;
            this.error = '';
            const res = await fetch(`/ai/conversations/${id}`, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            this.messages = data.messages || [];
            this.$nextTick(() => {
                const el = document.getElementById('aiChatMessages');
                if (el) el.scrollTop = el.scrollHeight;
            });
        },
        async sendMessage() {
            if (!this.input.trim() || this.loading) return;
            this.loading = true;
            this.error = '';
            const text = this.input.trim();
            this.messages.push({ role: 'user', content: text });
            this.input = '';
            try {
                const res = await fetch(this.chatUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ message: text, conversation_id: this.activeId }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Error');
                this.activeId = data.conversation_id;
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
                this.$nextTick(() => {
                    const el = document.getElementById('aiChatMessages');
                    if (el) el.scrollTop = el.scrollHeight;
                });
            }
        },
        async deleteConversation(id) {
            if (!confirm(config.deleteConfirm)) return;
            await fetch(`/ai/conversations/${id}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf },
            });
            this.conversations = this.conversations.filter(c => c.id !== id);
            if (this.activeId === id) this.newConversation();
        },
        async runTool() {
            this.toolLoading = true;
            this.toolResult = '';
            const urls = { email: '{{ route('ai.email') }}', translate: '{{ route('ai.translate') }}', summarize: '{{ route('ai.summarize') }}' };
            const body = this.tool === 'translate'
                ? { text: this.toolInput, from: '{{ app()->getLocale() }}', to: 'en' }
                : { context: this.toolInput, data: this.toolInput };
            const res = await fetch(urls[this.tool], {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            this.toolResult = data.result || '';
            this.toolLoading = false;
        },
    };
}
</script>
@endpush
