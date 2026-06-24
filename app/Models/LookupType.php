<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LookupType extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'is_system', 'is_active'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean', 'is_active' => 'boolean'];
    }

    public function values(): HasMany
    {
        return $this->hasMany(LookupValue::class);
    }

    protected static function booted(): void
    {
        static::saved(fn () => registry()->flush());
        static::deleted(fn () => registry()->flush());
    }
}
