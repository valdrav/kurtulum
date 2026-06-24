<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected ?string $apiKey;
    protected string $model;
    protected string $provider;

    public function __construct()
    {
        $this->apiKey = config('ticari.ai.api_key');
        $this->model = config('ticari.ai.model', 'gpt-4o-mini');
        $this->provider = config('ticari.ai.provider', 'openai');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function generateEmail(string $context, string $tone = 'professional', string $language = 'tr'): ?string
    {
        $prompt = "Write a {$tone} business email in {$language} based on this context:\n\n{$context}\n\nReturn only the email body.";

        return $this->chat($prompt);
    }

    public function summarizeReport(string $data, string $language = 'tr'): ?string
    {
        $prompt = "Summarize this business report data in {$language}. Highlight key metrics and actionable insights:\n\n{$data}";

        return $this->chat($prompt);
    }

    public function operationSuggestions(array $shipmentData, string $language = 'tr'): ?string
    {
        $json = json_encode($shipmentData, JSON_UNESCAPED_UNICODE);
        $prompt = "As a logistics expert for an export company, analyze this shipment and provide operation suggestions in {$language}:\n\n{$json}";

        return $this->chat($prompt);
    }

    public function financialAnalysis(array $financeData, string $language = 'tr'): ?string
    {
        $json = json_encode($financeData, JSON_UNESCAPED_UNICODE);
        $prompt = "Analyze this financial data for an export company and provide insights in {$language}:\n\n{$json}";

        return $this->chat($prompt);
    }

    public function translate(string $text, string $from, string $to): ?string
    {
        $prompt = "Translate the following text from {$from} to {$to}. Return only the translation:\n\n{$text}";

        return $this->chat($prompt);
    }

    protected function chat(string $prompt): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant for an export/logistics ERP system.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
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
