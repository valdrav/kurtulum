<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPortalAccess extends Model
{
    protected $table = 'customer_portal_access';

    protected $fillable = [
        'customer_id',
        'user_id',
        'is_active',
        'view_orders',
        'view_shipments',
        'view_shipment_costs',
        'view_directory',
        'edit_profile',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'view_orders' => 'boolean',
            'view_shipments' => 'boolean',
            'view_shipment_costs' => 'boolean',
            'view_directory' => 'boolean',
            'edit_profile' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allows(string $feature): bool
    {
        return match ($feature) {
            'orders' => $this->view_orders,
            'shipments' => $this->view_shipments,
            'shipment_costs' => $this->view_shipment_costs,
            'directory' => $this->view_directory,
            'edit_profile' => $this->edit_profile,
            default => false,
        };
    }
}
