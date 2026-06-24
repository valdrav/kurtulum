<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'checklist',
        'labels',
        'status',
        'priority',
        'due_date',
        'reminder_at',
        'estimated_hours',
        'completed_at',
        'assigned_to',
        'created_by',
        'taskable_type',
        'taskable_id',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'reminder_at' => 'datetime',
            'completed_at' => 'datetime',
            'checklist' => 'array',
            'labels' => 'array',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checklistProgress(): array
    {
        $items = $this->checklist ?? [];
        $done = collect($items)->where('done', true)->count();

        return ['done' => $done, 'total' => count($items)];
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }
}
