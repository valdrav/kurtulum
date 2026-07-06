<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmployeeHrDetail extends Model
{
    protected $fillable = [
        'employee_id', 'birth_date', 'national_id', 'birth_place', 'marital_status',
        'address', 'emergency_contact', 'emergency_phone', 'bank_name', 'iban',
        'base_salary', 'salary_currency', 'cv_data', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'base_salary' => 'decimal:2',
            'cv_data' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
