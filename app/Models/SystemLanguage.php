<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemLanguage extends Model
{
    protected $fillable = [
        'code', 'name', 'native_name', 'direction', 'flag',
        'is_active', 'is_default', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_default' => 'boolean'];
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
