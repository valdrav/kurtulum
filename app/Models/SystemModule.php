<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemModule extends Model
{
    protected $fillable = [
        'slug', 'name', 'version', 'description', 'provider_class',
        'path', 'manifest', 'config', 'is_enabled', 'is_core', 'installed_at',
    ];

    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'config' => 'array',
            'is_enabled' => 'boolean',
            'is_core' => 'boolean',
            'installed_at' => 'datetime',
        ];
    }
}
