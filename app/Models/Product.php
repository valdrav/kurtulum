<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'unit',
        'hs_code',
        'weight',
        'dimensions',
        'unit_price',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
