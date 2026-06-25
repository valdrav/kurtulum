<?php

namespace App\Services;

use App\Models\Email;
use App\Models\EmailAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailAttachmentService
{
    /** @var array<int, string> */
    protected array $typeNames = [
        0 => 'text',
        1 => 'multipart',
        2 => 'message',
        3 => 'application',
        4 => 'audio',
        5 => 'image',
        6 => 'video',
        7 => 'other',
    ];

    public function syncAttachments($connection, int $msgNo, $structure, Email $email): int
    {
        if (! $structure) {
            return 0;
        }

        $parts = [];
        $this->collectAttachmentParts($connection, $msgNo, $structure, '', $parts);

        if ($parts === []) {
            return 0;
        }

        $existing = $email->attachments()->pluck('part_key')->all();
        $added = 0;

        foreach ($parts as $part) {
            if (in_array($part['part_key'], $existing, true)) {
                continue;
            }

            $path = $this->storeFile($email, $part['filename'], $part['data']);

            EmailAttachment::create([
                'email_id' => $email->id,
                'part_key' => $part['part_key'],
                'filename' => $part['filename'],
                'mime_type' => $part['mime_type'],
                'size' => strlen($part['data']),
                'storage_path' => $path,
            ]);

            $existing[] = $part['part_key'];
            $added++;
        }

        return $added;
    }

    /**
     * @param array<int, array{part_key: string, filename: string, mime_type: string, data: string}> $attachments
     */
    protected function collectAttachmentParts($connection, int $msgNo, $structure, string $partNumber, array &$attachments): void
    {
        $type = $structure->type ?? 0;

        if ($type === 1 && ! empty($structure->parts)) {
            foreach ($structure->parts as $index => $part) {
                $subPart = $partNumber === '' ? (string) ($index + 1) : $partNumber . '.' . ($index + 1);
                $this->collectAttachmentParts($connection, $msgNo, $part, $subPart, $attachments);
            }

            return;
        }

        if ($type === 2 && ! empty($structure->parts[0])) {
            $subPart = $partNumber === '' ? '1' : $partNumber . '.1';
            $this->collectAttachmentParts($connection, $msgNo, $structure->parts[0], $subPart, $attachments);

            return;
        }

        if (! $this->isAttachmentPart($structure)) {
            return;
        }

        $fetchPart = $partNumber === '' ? '1' : $partNumber;
        $data = $this->decodePart($connection, $msgNo, $fetchPart, $structure);

        if ($data === null || $data === '') {
            return;
        }

        $attachments[] = [
            'part_key' => $fetchPart,
            'filename' => $this->resolveFilename($structure, $attachments),
            'mime_type' => $this->resolveMime($structure),
            'data' => $data,
        ];
    }

    protected function isAttachmentPart($structure): bool
    {
        $disposition = strtolower($structure->disposition ?? '');
        $subtype = strtolower($structure->subtype ?? '');
        $type = $structure->type ?? 0;
        $filename = $this->extractFilename($structure);

        if ($disposition === 'attachment') {
            return true;
        }

        if ($type === 0 && in_array($subtype, ['plain', 'html'], true)) {
            return false;
        }

        $contentId = trim((string) ($structure->id ?? ''), '<> ');

        if ($contentId !== '' && ($disposition === 'inline' || $disposition === '')) {
            if ($type === 5 || in_array($subtype, ['jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)) {
                return false;
            }
        }

        if ($filename !== null && $filename !== '') {
            if ($type === 0 && in_array($subtype, ['plain', 'html'], true)) {
                return false;
            }

            return true;
        }

        if ($type === 3 && $disposition !== 'inline') {
            return true;
        }

        return false;
    }

    protected function extractFilename($structure): ?string
    {
        foreach (['parameters', 'dparameters'] as $prop) {
            if (empty($structure->$prop)) {
                continue;
            }

            foreach ($structure->$prop as $param) {
                $attr = strtolower($param->attribute ?? '');

                if (in_array($attr, ['name', 'filename'], true)) {
                    return $this->decodeFilename($param->value ?? '');
                }
            }
        }

        return null;
    }

    protected function decodeFilename(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return '';
        }

        if (function_exists('imap_utf8')) {
            $decoded = @imap_utf8($name);

            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        if (function_exists('mb_decode_mimeheader')) {
            $decoded = @mb_decode_mimeheader($name);

            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return $name;
    }

    /**
     * @param array<int, array{part_key: string, filename: string, mime_type: string, data: string}> $existing
     */
    protected function resolveFilename($structure, array $existing): string
    {
        $name = $this->extractFilename($structure);

        if ($name) {
            return $this->sanitizeFilename($name);
        }

        $subtype = strtolower($structure->subtype ?? 'bin');
        $base = 'ek.' . ($subtype !== '' ? $subtype : 'bin');
        $used = array_column($existing, 'filename');
        $candidate = $base;
        $i = 2;

        while (in_array($candidate, $used, true)) {
            $candidate = pathinfo($base, PATHINFO_FILENAME) . '-' . $i . '.' . pathinfo($base, PATHINFO_EXTENSION);
            $i++;
        }

        return $candidate;
    }

    protected function sanitizeFilename(string $name): string
    {
        $name = str_replace(['\\', '/'], '-', $name);
        $name = preg_replace('/[\x00-\x1f\x7f]/u', '', $name) ?? $name;
        $name = trim($name, ".\t\n\r \0\x0B");

        if ($name === '') {
            return 'ek.bin';
        }

        return Str::limit($name, 180, '');
    }

    protected function resolveMime($structure): string
    {
        $type = $this->typeNames[$structure->type ?? 7] ?? 'application';
        $subtype = strtolower($structure->subtype ?? 'octet-stream');

        return $type . '/' . $subtype;
    }

    protected function decodePart($connection, int $msgNo, string $partNumber, $structure): ?string
    {
        $raw = imap_fetchbody($connection, $msgNo, $partNumber);

        if ($raw === false || $raw === '') {
            return null;
        }

        $encoding = $structure->encoding ?? 0;

        $body = match ($encoding) {
            3 => base64_decode($raw, true) ?: $raw,
            4 => quoted_printable_decode($raw),
            default => $raw,
        };

        return is_string($body) && $body !== '' ? $body : null;
    }

    protected function storeFile(Email $email, string $filename, string $data): string
    {
        $safe = Str::slug(pathinfo($filename, PATHINFO_FILENAME), '_') ?: 'ek';
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $storedName = $safe . ($ext ? '.' . $ext : '');
        $path = 'email-attachments/' . $email->email_account_id . '/' . $email->id . '/' . Str::uuid() . '_' . $storedName;

        Storage::disk('local')->put($path, $data);

        return $path;
    }
}
