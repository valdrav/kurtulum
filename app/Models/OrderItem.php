<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'discount',
        'tax_rate',
        'total',
        'purchase_unit_price',
        'purchase_discount_percent',
        'sale_unit_price',
        'purchase_total',
        'margin_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'purchase_unit_price' => 'decimal:4',
            'purchase_discount_percent' => 'decimal:2',
            'sale_unit_price' => 'decimal:4',
            'discount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'total' => 'decimal:2',
            'purchase_total' => 'decimal:2',
            'margin_amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
