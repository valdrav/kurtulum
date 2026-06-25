<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailInlineImageService
{
    /** @var array<string, string> */
    protected array $mimeExtensions = [
        'jpeg' => 'jpg',
        'jpg' => 'jpg',
        'png' => 'png',
        'gif' => 'gif',
        'webp' => 'webp',
        'bmp' => 'bmp',
        'svg' => 'svg',
    ];

    public function embedInlineImages($connection, int $msgNo, $structure, ?string $html, int $accountId, string $messageId): ?string
    {
        if ($html === null || $html === '' || ! str_contains($html, 'cid:')) {
            return $html;
        }

        $inline = [];
        $this->collectInlineParts($connection, $msgNo, $structure, '', $inline);

        if ($inline === []) {
            return $html;
        }

        $folder = 'email-inline/' . $accountId . '/' . md5($messageId);

        foreach ($inline as $cid => $meta) {
            $ext = $this->mimeExtensions[strtolower($meta['subtype'])] ?? 'bin';
            $safeName = Str::slug($cid, '_') ?: 'image';
            $path = $folder . '/' . $safeName . '.' . $ext;

            Storage::disk('public')->put($path, $meta['data']);
            $url = url('/media/' . $path);

            $html = str_ireplace([
                'cid:' . $cid,
                'cid:&lt;' . $cid . '&gt;',
                'cid:<' . $cid . '>',
            ], $url, $html);
        }

        return $html;
    }

    /**
     * @param array<string, array{data: string, subtype: string}> $inline
     */
    protected function collectInlineParts($connection, int $msgNo, $structure, string $partNumber, array &$inline): void
    {
        if (($structure->type ?? 0) === 1 && ! empty($structure->parts)) {
            foreach ($structure->parts as $index => $part) {
                $subPart = $partNumber === '' ? (string) ($index + 1) : $partNumber . '.' . ($index + 1);
                $this->collectInlineParts($connection, $msgNo, $part, $subPart, $inline);
            }

            return;
        }

        $cid = trim((string) ($structure->id ?? ''), '<> ');

        if ($cid === '') {
            return;
        }

        $subtype = strtolower($structure->subtype ?? '');
        $isImage = isset($this->mimeExtensions[$subtype])
            || ($structure->type ?? 0) === 5;

        if (! $isImage && strtolower($structure->disposition ?? '') !== 'inline') {
            return;
        }

        $fetchPart = $partNumber === '' ? '1' : $partNumber;
        $data = $this->decodePart($connection, $msgNo, $fetchPart, $structure);

        if ($data !== null && $data !== '') {
            $inline[$cid] = [
                'data' => $data,
                'subtype' => $subtype !== '' ? $subtype : 'png',
            ];
        }
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
}
