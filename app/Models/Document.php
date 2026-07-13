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

    public function iconClass(): string
    {
        $mime = strtolower((string) $this->mime_type);
        $name = strtolower((string) ($this->original_name ?: $this->name));

        if (str_contains($mime, 'pdf') || str_ends_with($name, '.pdf')) {
            return 'ti-file-type-pdf';
        }

        if (str_contains($mime, 'word') || str_ends_with($name, '.doc') || str_ends_with($name, '.docx')) {
            return 'ti-file-type-doc';
        }

        if (str_contains($mime, 'sheet') || str_contains($mime, 'excel') || str_ends_with($name, '.xls') || str_ends_with($name, '.xlsx') || str_ends_with($name, '.csv')) {
            return 'ti-file-type-xls';
        }

        if (str_starts_with($mime, 'image/')) {
            return 'ti-photo';
        }

        if (str_contains($mime, 'zip') || str_ends_with($name, '.zip') || str_ends_with($name, '.rar')) {
            return 'ti-file-zip';
        }

        if (str_contains($mime, 'text') || str_ends_with($name, '.txt')) {
            return 'ti-file-text';
        }

        return 'ti-file';
    }

    public function iconTone(): string
    {
        return match ($this->iconClass()) {
            'ti-file-type-pdf' => 'pdf',
            'ti-file-type-doc' => 'doc',
            'ti-file-type-xls' => 'xls',
            'ti-photo' => 'image',
            'ti-file-zip' => 'zip',
            'ti-file-text' => 'text',
            default => 'file',
        };
    }

    public function isPreviewable(): bool
    {
        $mime = strtolower((string) $this->mime_type);

        return str_contains($mime, 'pdf') || str_starts_with($mime, 'image/');
    }

    public function openUrl(): string
    {
        return $this->isPreviewable()
            ? route('documents.preview', $this)
            : route('documents.download', $this);
    }

    public function folderSlug(): string
    {
        $folder = trim((string) $this->folder);

        return $folder !== '' ? $folder : '__default';
    }

    public function purgePhysicalFile(): bool
    {
        if (! $this->path) {
            return false;
        }

        return app(\App\Services\DocumentStorageService::class)
            ->purgePhysicalFile($this->path, $this->disk ?: 'local');
    }

    protected static function booted(): void
    {
        static::deleting(function (Document $document) {
            $document->purgePhysicalFile();
        });
    }
}
