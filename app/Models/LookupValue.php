<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LookupValue extends Model
{
    protected $fillable = [
        'lookup_type_id', 'code', 'label', 'meta', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array', 'is_active' => 'boolean'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LookupType::class, 'lookup_type_id');
    }

    protected static function booted(): void
    {
        static::saved(fn () => registry()->flush());
        static::deleted(fn () => registry()->flush());
    }
}
