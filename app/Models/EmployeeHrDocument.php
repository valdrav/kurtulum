<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EmployeeHrDocument extends Model
{
    protected $fillable = [
        'employee_id', 'category', 'title', 'path', 'disk', 'size',
        'document_date', 'expires_at', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function downloadUrl(): string
    {
        return route('hr.documents.download', $this);
    }
}
