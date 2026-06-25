<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmailAttachment extends Model
{
    use HasUuid;

    protected $fillable = [
        'email_id',
        'part_key',
        'filename',
        'mime_type',
        'size',
        'storage_path',
    ];

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }

    public function iconClass(): string
    {
        $mime = strtolower((string) $this->mime_type);
        $name = strtolower($this->filename);

        if (str_contains($mime, 'pdf') || str_ends_with($name, '.pdf')) {
            return 'ti-file-type-pdf';
        }

        if (str_contains($mime, 'word') || str_ends_with($name, '.doc') || str_ends_with($name, '.docx')) {
            return 'ti-file-type-doc';
        }

        if (str_contains($mime, 'sheet') || str_contains($mime, 'excel') || str_ends_with($name, '.xls') || str_ends_with($name, '.xlsx')) {
            return 'ti-file-type-xls';
        }

        if (str_starts_with($mime, 'image/')) {
            return 'ti-photo';
        }

        if (str_contains($mime, 'zip') || str_ends_with($name, '.zip') || str_ends_with($name, '.rar')) {
            return 'ti-file-zip';
        }

        return 'ti-paperclip';
    }
}
