<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class EmailAccount extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'email',
        'name',
        'provider',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'credentials',
        'signature_html',
        'signature_auto',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'imap_port' => 'integer',
            'smtp_port' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'signature_auto' => 'boolean',
        ];
    }

    protected $hidden = [
        'credentials',
    ];

    public static function pleskPresetForEmail(string $email): array
    {
        $domain = strtolower(trim((string) substr(strrchr($email, '@') ?: '', 1)));

        if ($domain === '') {
            $domain = 'localhost';
        }

        return [
            'imap_host' => $domain,
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => $domain,
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
        ];
    }

    public static function applyProviderDefaults(array $validated): array
    {
        if ($validated['provider'] === 'plesk') {
            $preset = self::pleskPresetForEmail($validated['email']);
            foreach ($preset as $key => $value) {
                if (empty($validated[$key])) {
                    $validated[$key] = $value;
                }
            }
        } elseif (in_array($validated['provider'], ['microsoft365', 'google', 'yandex'], true)) {
            $validated = array_merge(self::providerPresets()[$validated['provider']], $validated);
        }

        return $validated;
    }

    public static function providerPresets(): array
    {
        return [
            'plesk' => self::pleskPresetForEmail('user@example.com'),
            'microsoft365' => [
                'imap_host' => 'outlook.office365.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_host' => 'smtp.office365.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
            ],
            'google' => [
                'imap_host' => 'imap.gmail.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
            ],
            'yandex' => [
                'imap_host' => 'imap.yandex.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_host' => 'smtp.yandex.com',
                'smtp_port' => 465,
                'smtp_encryption' => 'ssl',
            ],
        ];
    }

    public function setCredentialsFromRequest(?string $username, ?string $password): void
    {
        if ($password === null || $password === '') {
            return;
        }

        $this->credentials = Crypt::encryptString(json_encode([
            'username' => $username ?: $this->email,
            'password' => $password,
        ]));
    }

    public function syncCredentials(?string $username, ?string $password): void
    {
        $current = $this->getCredentials();
        $newUsername = $username ?: ($current['username'] ?? $this->email);
        $newPassword = ($password !== null && $password !== '') ? $password : ($current['password'] ?? '');

        if ($newPassword === '') {
            return;
        }

        $this->credentials = Crypt::encryptString(json_encode([
            'username' => $newUsername,
            'password' => $newPassword,
        ]));
    }

    public function getCredentials(): array
    {
        if (! $this->credentials) {
            return [];
        }

        try {
            return json_decode(Crypt::decryptString($this->credentials), true) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function smtpUsername(): ?string
    {
        return $this->getCredentials()['username'] ?? $this->email;
    }

    public function smtpPassword(): ?string
    {
        return $this->getCredentials()['password'] ?? null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }
}
