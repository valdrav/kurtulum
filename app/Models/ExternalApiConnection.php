<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalApiConnection extends Model
{
    protected $fillable = [
        'name',
        'customer_id',
        'token_prefix',
        'token_hash',
        'is_active',
        'view_customer',
        'view_directory',
        'view_orders',
        'view_shipments',
        'view_shipment_costs',
        'last_used_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'view_customer' => 'boolean',
            'view_directory' => 'boolean',
            'view_orders' => 'boolean',
            'view_shipments' => 'boolean',
            'view_shipment_costs' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allows(string $feature): bool
    {
        return match ($feature) {
            'customer' => $this->view_customer,
            'directory' => $this->view_directory,
            'orders' => $this->view_orders,
            'shipments' => $this->view_shipments,
            'shipment_costs' => $this->view_shipment_costs,
            default => false,
        };
    }

    /** @return array<string, bool> */
    public function permissionsSummary(): array
    {
        return [
            'customer' => $this->view_customer,
            'directory' => $this->view_directory,
            'orders' => $this->view_orders,
            'shipments' => $this->view_shipments,
            'shipment_costs' => $this->view_shipment_costs,
        ];
    }
}
