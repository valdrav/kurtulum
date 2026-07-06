<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleProgram extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'year', 'month', 'week_number', 'week_start', 'week_end',
        'title', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class)->orderBy('sort_order')->orderBy('entry_date');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function displayTitle(): string
    {
        if ($this->title) {
            return $this->title;
        }

        $monthNames = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];

        $monthLabel = $monthNames[$this->month] ?? str_pad((string) $this->month, 2, '0', STR_PAD_LEFT);

        return sprintf('%s %d Aylık Program', $monthLabel, $this->year);
    }

    public function monthLabel(): string
    {
        return $this->displayTitle();
    }
}
