<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleEntry extends Model
{
    protected $fillable = [
        'schedule_program_id', 'entry_date', 'sort_order', 'data',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'data' => 'array',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ScheduleProgram::class, 'schedule_program_id');
    }

    public function value(string $key): string
    {
        return (string) ($this->data[$key] ?? '');
    }
}
