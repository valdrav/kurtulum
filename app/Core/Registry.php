<?php

namespace App\Core;

use App\Models\LookupType;
use App\Models\LookupValue;
use App\Models\SystemCurrency;
use App\Models\SystemLanguage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Registry
{
    public function languages(bool $activeOnly = true): Collection
    {
        return Cache::remember('registry.languages.' . ($activeOnly ? 'active' : 'all'), 3600, function () use ($activeOnly) {
            $query = SystemLanguage::orderBy('sort_order');
            if ($activeOnly) {
                $query->where('is_active', true);
            }
            return $query->get();
        });
    }

    public function languageCodes(bool $activeOnly = true): array
    {
        return $this->languages($activeOnly)->pluck('code')->all();
    }

    public function languagesForSelect(): array
    {
        return $this->languages()->mapWithKeys(fn ($l) => [
            $l->code => ['name' => $l->native_name ?: $l->name, 'dir' => $l->direction, 'flag' => $l->flag],
        ])->all();
    }

    public function defaultLanguage(): ?SystemLanguage
    {
        return Cache::remember('registry.language.default', 3600, fn () =>
            SystemLanguage::where('is_default', true)->where('is_active', true)->first()
            ?? SystemLanguage::where('is_active', true)->orderBy('sort_order')->first()
        );
    }

    public function currencies(bool $activeOnly = true): Collection
    {
        return Cache::remember('registry.currencies.' . ($activeOnly ? 'active' : 'all'), 3600, function () use ($activeOnly) {
            $query = SystemCurrency::orderBy('sort_order');
            if ($activeOnly) {
                $query->where('is_active', true);
            }
            return $query->get();
        });
    }

    public function currencyCodes(bool $activeOnly = true): array
    {
        return $this->currencies($activeOnly)->pluck('code')->all();
    }

    public function defaultCurrency(): ?SystemCurrency
    {
        return Cache::remember('registry.currency.default', 3600, fn () =>
            SystemCurrency::where('is_default', true)->where('is_active', true)->first()
            ?? SystemCurrency::where('is_active', true)->orderBy('sort_order')->first()
        );
    }

    public function formatMoney(float $amount, ?string $currencyCode = null): string
    {
        $currency = $currencyCode
            ? $this->currencies()->firstWhere('code', $currencyCode)
            : $this->defaultCurrency();

        if (!$currency) {
            return number_format($amount, 2);
        }

        return $currency->symbol . ' ' . number_format($amount, $currency->decimal_places, ',', '.');
    }

    public function lookup(string $typeSlug, bool $activeOnly = true): Collection
    {
        return Cache::remember("registry.lookup.{$typeSlug}." . ($activeOnly ? 'active' : 'all'), 3600, function () use ($typeSlug, $activeOnly) {
            $type = LookupType::where('slug', $typeSlug)->where('is_active', true)->first();
            if (!$type) {
                return collect();
            }
            $query = $type->values()->orderBy('sort_order');
            if ($activeOnly) {
                $query->where('is_active', true);
            }
            return $query->get();
        });
    }

    public function lookupOptions(string $typeSlug): array
    {
        return $this->lookup($typeSlug)->pluck('label', 'code')->all();
    }

    public function flush(): void
    {
        Cache::forget('registry.languages.active');
        Cache::forget('registry.languages.all');
        Cache::forget('registry.language.default');
        Cache::forget('registry.currencies.active');
        Cache::forget('registry.currencies.all');
        Cache::forget('registry.currency.default');
        Cache::forget('payment_methods.all');
        Cache::forget('system.menu.items');

        foreach (LookupType::pluck('slug') as $slug) {
            Cache::forget("registry.lookup.{$slug}.active");
            Cache::forget("registry.lookup.{$slug}.all");
        }
    }
}
