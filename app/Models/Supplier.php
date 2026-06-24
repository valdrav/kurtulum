<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'company_name',
        'tax_number',
        'tax_office',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'country',
        'postal_code',
        'currency',
        'payment_terms_days',
        'status',
        'notes',
        'assigned_user_id',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms_days' => 'integer',
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipmentCosts(): HasMany
    {
        return $this->hasMany(ShipmentCost::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
