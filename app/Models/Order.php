<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'supplier_id',
        'status',
        'order_date',
        'delivery_date',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'purchase_total',
        'sale_total',
        'margin_total',
        'amount_collected',
        'amount_paid',
        'finance_posted_at',
        'shipping_address',
        'shipping_city',
        'shipping_country',
        'shipping_postal_code',
        'incoterm',
        'notes',
        'assigned_user_id',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'purchase_total' => 'decimal:2',
            'sale_total' => 'decimal:2',
            'margin_total' => 'decimal:2',
            'amount_collected' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'finance_posted_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function lettersOfCredit(): HasMany
    {
        return $this->hasMany(LetterOfCredit::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function emails(): MorphMany
    {
        return $this->morphMany(Email::class, 'emailable');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }
}
