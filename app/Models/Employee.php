<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'hire_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function hrDetail(): HasOne
    {
        return $this->hasOne(EmployeeHrDetail::class);
    }

    public function hrDocuments(): HasMany
    {
        return $this->hasMany(EmployeeHrDocument::class);
    }

    public function compensations(): HasMany
    {
        return $this->hasMany(EmployeeCompensation::class)->latest('payment_date');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
