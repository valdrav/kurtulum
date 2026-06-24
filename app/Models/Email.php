<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'email_account_id',
        'message_id',
        'subject',
        'body_html',
        'body_text',
        'from_email',
        'from_name',
        'to',
        'cc',
        'bcc',
        'sent_at',
        'received_at',
        'direction',
        'is_read',
        'is_starred',
        'emailable_type',
        'emailable_id',
    ];

    protected function casts(): array
    {
        return [
            'to' => 'array',
            'cc' => 'array',
            'bcc' => 'array',
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
            'is_read' => 'boolean',
            'is_starred' => 'boolean',
        ];
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function emailable(): MorphTo
    {
        return $this->morphTo();
    }
}
