<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected ?string $apiKey;
    protected string $model;
    protected string $provider;
    protected ?string $groqApiKey;
    protected string $groqModel;

    public function __construct()
    {
        $this->provider = Setting::get('ai_provider') ?: config('ticari.ai.provider', 'groq');
        $this->apiKey = Setting::get('ai_api_key') ?: config('ticari.ai.api_key');
        $this->model = Setting::get('ai_model') ?: config('ticari.ai.model', 'gpt-4o-mini');
        $this->groqApiKey = Setting::get('groq_api_key') ?: config('ticari.ai.groq_api_key');
        $this->groqModel = Setting::get('groq_model') ?: config('ticari.ai.groq_model', 'llama-3.1-8b-instant');
    }

    public function isConfigured(): bool
    {
        if (Setting::get('ai_enabled', '1') !== '1') {
            return false;
        }

        return ! empty($this->apiKey) || ! empty($this->groqApiKey);
    }

    public function activeProviderLabel(): string
    {
        if ($this->provider === 'groq' && ! empty($this->groqApiKey)) {
            return 'Groq';
        }

        if (! empty($this->apiKey)) {
            return 'OpenAI';
        }

        if (! empty($this->groqApiKey)) {
            return 'Groq';
        }

        return '';
    }

    /** @param  array<int, array{role: string, content: string}>  $messages */
    public function chatMessages(array $messages, string $language = 'tr'): ?string
    {
        $system = 'You are ExportFlow ERP assistant for export/logistics/finance. '
            . 'Reply in ' . $language . '. Be concise and practical.';

        array_unshift($messages, ['role' => 'system', 'content' => $system]);

        return $this->requestChat($messages);
    }

    public function generateEmail(string $context, string $tone = 'professional', string $language = 'tr'): ?string
    {
        $prompt = "Write a {$tone} business email in {$language} based on this context:\n\n{$context}\n\nReturn only the email body.";

        return $this->chat($prompt, $language);
    }

    public function summarizeReport(string $data, string $language = 'tr'): ?string
    {
        $prompt = "Summarize this business report data in {$language}. Highlight key metrics and actionable insights:\n\n{$data}";

        return $this->chat($prompt, $language);
    }

    public function operationSuggestions(array $shipmentData, string $language = 'tr'): ?string
    {
        $json = json_encode($shipmentData, JSON_UNESCAPED_UNICODE);
        $prompt = "As a logistics expert for an export company, analyze this shipment and provide operation suggestions in {$language}:\n\n{$json}";

        return $this->chat($prompt, $language);
    }

    public function financialAnalysis(array $financeData, string $language = 'tr'): ?string
    {
        $json = json_encode($financeData, JSON_UNESCAPED_UNICODE);
        $prompt = "Analyze this financial data for an export company and provide insights in {$language}:\n\n{$json}";

        return $this->chat($prompt, $language);
    }

    public function translate(string $text, string $from, string $to): ?string
    {
        $prompt = "Translate the following text from {$from} to {$to}. Return only the translation:\n\n{$text}";

        return $this->chat($prompt, $to);
    }

    protected function chat(string $prompt, string $language = 'tr'): ?string
    {
        return $this->chatMessages([['role' => 'user', 'content' => $prompt]], $language);
    }

    /** @param  array<int, array{role: string, content: string}>  $messages */
    protected function requestChat(array $messages): ?string
    {
        if ($this->provider === 'groq' && ! empty($this->groqApiKey)) {
            $result = $this->postChat('https://api.groq.com/openai/v1/chat/completions', $this->groqApiKey, $this->groqModel, $messages);
            if ($result !== null) {
                return $result;
            }
        }

        if (! empty($this->apiKey)) {
            $result = $this->postChat('https://api.openai.com/v1/chat/completions', $this->apiKey, $this->model, $messages);
            if ($result !== null) {
                return $result;
            }
        }

        if (! empty($this->groqApiKey)) {
            return $this->postChat('https://api.groq.com/openai/v1/chat/completions', $this->groqApiKey, $this->groqModel, $messages);
        }

        return null;
    }

    /** @param  array<int, array{role: string, content: string}>  $messages */
    protected function postChat(string $url, string $apiKey, string $model, array $messages): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(90)->post($url, [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            Log::warning('AI API error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('AI service error: ' . $e->getMessage());
        }

        return null;
    }
}
