<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemCurrency extends Model
{
    protected $fillable = [
        'code', 'name', 'symbol', 'decimal_places', 'exchange_rate', 'tcmb_rate', 'market_rate', 'rate_updated_at',
        'is_active', 'is_default', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:8',
            'tcmb_rate' => 'decimal:8',
            'market_rate' => 'decimal:8',
            'rate_updated_at' => 'datetime',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => registry()->flush());
        static::deleted(fn () => registry()->flush());
    }

    public function setAsDefault(): void
    {
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->update(['is_default' => true, 'is_active' => true]);
    }
}
