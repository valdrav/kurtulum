<?php

namespace App\Services;

use App\Models\ContentTranslation;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class ContentTranslationService
{
    /** @var array<string, string> */
    protected static array $localeNames = [
        'tr' => 'Turkish',
        'en' => 'English',
        'de' => 'German',
        'fr' => 'French',
        'ar' => 'Arabic',
    ];

    public function isEnabled(): bool
    {
        if (Setting::get('auto_translate_enabled', '1') !== '1') {
            return false;
        }

        return app(AiService::class)->isConfigured();
    }

    public function translate(?string $text, ?string $targetLocale = null, ?string $sourceLocale = null): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        $target = $targetLocale ?? app()->getLocale();
        $source = $sourceLocale ?? config('ticari.auto_translate.source_locale', 'tr');

        if ($target === $source) {
            return $text;
        }

        if (! $this->isEnabled()) {
            return $text;
        }

        if (mb_strlen($text) > 5000) {
            return $text;
        }

        $hash = hash('sha256', $source.'|'.$text);

        $memoryKey = "content_trans:{$target}:{$hash}";
        $cached = Cache::get($memoryKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $stored = ContentTranslation::query()
            ->where('text_hash', $hash)
            ->where('target_locale', $target)
            ->first();

        if ($stored) {
            Cache::put($memoryKey, $stored->translated_text, now()->addDays(30));

            return $stored->translated_text;
        }

        $from = self::$localeNames[$source] ?? $source;
        $to = self::$localeNames[$target] ?? $target;

        $translated = app(AiService::class)->translate($text, $from, $to) ?? $text;
        $translated = trim($translated);

        if ($translated === '') {
            return $text;
        }

        ContentTranslation::updateOrCreate(
            ['text_hash' => $hash, 'target_locale' => $target],
            [
                'source_locale' => $source,
                'source_text' => $text,
                'translated_text' => $translated,
            ]
        );

        Cache::put($memoryKey, $translated, now()->addDays(30));

        return $translated;
    }

    /** @param  iterable<string|null>  $texts */
    public function translateMany(iterable $texts, ?string $targetLocale = null, ?string $sourceLocale = null): array
    {
        $results = [];

        foreach ($texts as $key => $text) {
            $results[$key] = $this->translate($text, $targetLocale, $sourceLocale);
        }

        return $results;
    }
}
