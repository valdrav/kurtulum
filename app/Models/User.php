<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, HasUuid, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'locale',
        'theme',
        'phone',
        'avatar',
        'is_active',
        'user_type',
        'customer_id',
        'department_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function portalCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function portalAccess(): HasOne
    {
        return $this->hasOne(CustomerPortalAccess::class);
    }

    public function activePortalAccess(): HasOne
    {
        return $this->hasOne(CustomerPortalAccess::class)->where('is_active', true);
    }

    public function isPortalUser(): bool
    {
        return $this->usesCustomerPortal();
    }

    public function isStaffUser(): bool
    {
        return ! $this->usesCustomerPortal();
    }

    /** Aktif müşteri portalı erişimi var mı (tek müşteri) */
    public function usesCustomerPortal(): bool
    {
        if ($this->user_type === 'portal') {
            return true;
        }

        if ($this->relationLoaded('portalAccess')) {
            return (bool) ($this->portalAccess?->is_active);
        }

        return $this->portalAccess()->where('is_active', true)->exists();
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function emailAccounts(): HasMany
    {
        return $this->hasMany(EmailAccount::class);
    }

    public function assignedCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'assigned_user_id');
    }

    public function assignedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'assigned_user_id');
    }

    public function assignedShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'assigned_user_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }
}
