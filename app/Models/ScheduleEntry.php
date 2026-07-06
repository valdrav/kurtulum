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
        if (in_array($key, ['entry_date', 'cut_off_date', 'loading_date'], true)) {
            $raw = $key === 'entry_date'
                ? $this->entry_date?->format('Y-m-d')
                : ($this->data[$key] ?? '');

            if ($raw && $key !== 'entry_date') {
                try {
                    return \Carbon\Carbon::parse($raw)->format('d.m.Y');
                } catch (\Throwable) {
                    return (string) $raw;
                }
            }

            return $key === 'entry_date' ? ($this->entry_date?->format('d.m.Y') ?? '') : (string) $raw;
        }

        return (string) ($this->data[$key] ?? '');
    }

    public function rawValue(string $key): string
    {
        if ($key === 'entry_date') {
            return $this->entry_date?->format('Y-m-d') ?? '';
        }

        if (in_array($key, ['cut_off_date', 'loading_date'], true)) {
            return (string) ($this->data[$key] ?? '');
        }

        return (string) ($this->data[$key] ?? '');
    }
}
