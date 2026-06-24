<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description', 'type', 'icon',
        'config_schema', 'settings', 'features', 'supported_currencies',
        'fee_type', 'fee_amount', 'requires_reference', 'requires_bank_account',
        'is_online', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'config_schema' => 'array',
            'settings' => 'array',
            'features' => 'array',
            'supported_currencies' => 'array',
            'fee_amount' => 'decimal:4',
            'requires_reference' => 'boolean',
            'requires_bank_account' => 'boolean',
            'is_online' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => payment_methods()->flush());
        static::deleted(fn () => payment_methods()->flush());
    }

    public function getFormFields(): array
    {
        return $this->config_schema['fields'] ?? [];
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? [], true);
    }
}
