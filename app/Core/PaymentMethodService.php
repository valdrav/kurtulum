<?php

namespace App\Core;

use App\Models\PaymentMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PaymentMethodService
{
    public function __construct(protected Registry $registry, protected HookManager $hooks) {}

    public function all(bool $activeOnly = true): Collection
    {
        return Cache::remember('payment_methods.' . ($activeOnly ? 'active' : 'all'), 3600, function () use ($activeOnly) {
            $query = PaymentMethod::orderBy('sort_order');
            if ($activeOnly) {
                $query->where('is_active', true);
            }
            return $query->get();
        });
    }

    public function forType(string $type): Collection
    {
        return $this->all()->filter(fn (PaymentMethod $m) =>
            $m->type === $type || $m->type === 'both'
        );
    }

    public function forPayment(): Collection
    {
        return $this->forType('payment');
    }

    public function forCollection(): Collection
    {
        return $this->forType('collection');
    }

    public function findByCode(string $code): ?PaymentMethod
    {
        return $this->all(false)->firstWhere('code', $code);
    }

    public function supportedCurrencies(PaymentMethod $method): array
    {
        if (empty($method->supported_currencies)) {
            return $this->registry->currencyCodes();
        }
        return $method->supported_currencies;
    }

    public function buildValidationRules(PaymentMethod $method): array
    {
        $rules = [
            'account_id' => 'required|exists:accounts,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:10',
            'payment_date' => 'required|date',
        ];

        if ($method->requires_reference) {
            $rules['reference'] = 'required|string|max:255';
        } else {
            $rules['reference'] = 'nullable|string|max:255';
        }

        foreach ($method->getFormFields() as $field) {
            $key = 'method_data.' . $field['name'];
            $rules[$key] = ($field['required'] ?? false ? 'required' : 'nullable') . '|string|max:500';
        }

        return $this->hooks->filter('payment.validation_rules', $rules, $method);
    }

    public function calculateFee(PaymentMethod $method, float $amount): float
    {
        return match ($method->fee_type) {
            'fixed' => (float) $method->fee_amount,
            'percent' => round($amount * ((float) $method->fee_amount / 100), 2),
            default => 0,
        };
    }

    public function flush(): void
    {
        Cache::forget('payment_methods.active');
        Cache::forget('payment_methods.all');
    }
}
