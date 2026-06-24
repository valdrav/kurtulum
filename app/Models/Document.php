<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'name',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'category',
        'folder',
        'tags',
        'description',
        'uploaded_by',
        'is_confidential',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'tags' => 'array',
            'is_confidential' => 'boolean',
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function humanSize(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        return number_format($bytes / 1024, 1) . ' KB';
    }
}
