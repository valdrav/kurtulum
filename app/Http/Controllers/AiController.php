<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Services\AiService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(protected AiService $ai)
    {
        $this->middleware('permission:ai.view')->only([
            'index', 'showConversation', 'chat', 'destroyConversation',
            'generateEmail', 'summarizeReport', 'operationSuggestions',
            'financialAnalysis', 'translate',
        ]);
    }

    public function index()
    {
        $conversations = AiConversation::query()
            ->where('user_id', auth()->id())
            ->latest('updated_at')
            ->limit(30)
            ->get();

        $conversationTemplate = route('ai.conversations.show', ['conversation' => '__UUID__']);

        return view('ai.index', [
            'configured' => $this->ai->isConfigured(),
            'provider' => $this->ai->activeProviderLabel(),
            'conversations' => $conversations,
            'chatConfig' => [
                'chatUrl' => route('ai.chat'),
                'csrf' => csrf_token(),
                'configured' => $this->ai->isConfigured(),
                'locale' => app()->getLocale(),
                'conversationUrlTemplate' => $conversationTemplate,
                'toolUrls' => [
                    'email' => route('ai.email'),
                    'translate' => route('ai.translate'),
                    'summarize' => route('ai.summarize'),
                ],
                'toolTitles' => [
                    'email' => __('ai.generate_email'),
                    'translate' => __('ai.translate'),
                    'summarize' => __('ai.summarize'),
                ],
                'labels' => [
                    'placeholder' => __('ai.chat_placeholder'),
                    'deleteConfirm' => __('ai.delete_conversation_confirm'),
                    'newChat' => __('ai.new_chat'),
                    'you' => __('ai.you'),
                    'assistant' => __('ai.assistant'),
                    'toolPlaceholder' => __('ai.tool_placeholder'),
                    'loadFailed' => __('ai.load_failed'),
                    'sendFailed' => __('ai.request_failed'),
                    'deleteFailed' => __('ai.delete_failed'),
                ],
                'conversations' => $conversations->map(fn (AiConversation $c) => [
                    'id' => $c->uuid,
                    'title' => $c->title ?: __('ai.new_chat'),
                ])->values()->all(),
            ],
        ]);
    }

    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:8000',
            'conversation_id' => 'nullable|string|max:36',
        ]);

        if (! $this->ai->isConfigured()) {
            return response()->json(['error' => __('ai.not_configured')], 422);
        }

        $conversation = ! empty($validated['conversation_id'])
            ? AiConversation::query()
                ->where('user_id', auth()->id())
                ->where('uuid', $validated['conversation_id'])
                ->firstOrFail()
            : AiConversation::create([
                'user_id' => auth()->id(),
                'title' => str($validated['message'])->limit(60)->toString(),
            ]);

        AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        $history = $conversation->messages()
            ->latest('id')
            ->limit(20)
            ->get()
            ->reverse()
            ->map(fn (AiMessage $m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();

        $reply = $this->ai->chatMessages($history, app()->getLocale());

        if (! $reply) {
            return response()->json(['error' => __('ai.request_failed')], 502);
        }

        AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $reply,
        ]);

        $conversation->touch();

        return response()->json([
            'conversation_id' => $conversation->uuid,
            'reply' => $reply,
            'title' => $conversation->title,
        ]);
    }

    public function showConversation(AiConversation $conversation)
    {
        abort_unless($conversation->user_id === auth()->id(), 403);

        return response()->json([
            'id' => $conversation->uuid,
            'title' => $conversation->title,
            'messages' => $conversation->messages()->get(['role', 'content', 'created_at']),
        ]);
    }

    public function destroyConversation(AiConversation $conversation)
    {
        abort_unless($conversation->user_id === auth()->id(), 403);
        $conversation->delete();

        return response()->json(['ok' => true]);
    }

    public function generateEmail(Request $request)
    {
        $validated = $request->validate([
            'context' => 'required|string|max:5000',
            'tone' => 'nullable|string|max:50',
            'language' => 'nullable|in:tr,en,ar',
        ]);

        $result = $this->ai->generateEmail(
            $validated['context'],
            $validated['tone'] ?? 'professional',
            $validated['language'] ?? app()->getLocale()
        );

        return response()->json(['result' => $result ?? __('ai.not_configured')]);
    }

    public function summarizeReport(Request $request)
    {
        $validated = $request->validate(['data' => 'required|string|max:10000']);
        $result = $this->ai->summarizeReport($validated['data'], app()->getLocale());

        return response()->json(['result' => $result ?? __('ai.not_configured')]);
    }

    public function operationSuggestions(Request $request)
    {
        $validated = $request->validate(['shipment' => 'required|array']);
        $result = $this->ai->operationSuggestions($validated['shipment'], app()->getLocale());

        return response()->json(['result' => $result ?? __('ai.not_configured')]);
    }

    public function financialAnalysis(Request $request)
    {
        $validated = $request->validate(['data' => 'required|array']);
        $result = $this->ai->financialAnalysis($validated['data'], app()->getLocale());

        return response()->json(['result' => $result ?? __('ai.not_configured')]);
    }

    public function translate(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'from' => 'required|string|max:5',
            'to' => 'required|string|max:5',
        ]);

        $result = $this->ai->translate($validated['text'], $validated['from'], $validated['to']);

        return response()->json(['result' => $result ?? __('ai.not_configured')]);
    }
}
