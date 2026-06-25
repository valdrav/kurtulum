<?php

namespace App\Models;

use App\Services\EmailHtmlRenderer;
use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function attachments(): HasMany
    {
        return $this->hasMany(EmailAttachment::class);
    }

    public function hasAttachments(): bool
    {
        if ($this->relationLoaded('attachments')) {
            return $this->attachments->isNotEmpty();
        }

        if ($this->relationLoaded('attachments_count')) {
            return ($this->attachments_count ?? 0) > 0;
        }

        return $this->attachments()->exists();
    }

    public function previewText(int $length = 120): ?string
    {
        $text = trim((string) $this->body_text);

        if ($text === '') {
            $text = trim(html_entity_decode(strip_tags((string) $this->body_html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if ($text === '') {
            return null;
        }

        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '…' : $text;
    }

    public function sanitizedHtml(): ?string
    {
        return app(EmailHtmlRenderer::class)->prepareForDisplay($this->body_html);
    }

    public function hasRenderableBody(): bool
    {
        return $this->sanitizedHtml() !== null || trim((string) $this->body_text) !== '';
    }
}
