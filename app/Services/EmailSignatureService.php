<?php

namespace App\Services;

use App\Models\EmailAccount;

class EmailSignatureService
{
    public function sanitizeOutgoingHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<\/?(iframe|object|embed|form|input|button|meta|base)\b[^>]*>/is', '', $html) ?? $html;

        return trim($html);
    }

    public function htmlToText(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $text = preg_replace('/<\/p>/i', "\n\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /** @return array{html: string, text: string} */
    public function buildOutgoingBody(string $messageHtml, EmailAccount $account, bool $includeSignature): array
    {
        $messageHtml = $this->sanitizeOutgoingHtml($messageHtml);
        $signature = trim((string) $account->signature_html);

        if (! $includeSignature || $signature === '') {
            return [
                'html' => $messageHtml,
                'text' => $this->htmlToText($messageHtml),
            ];
        }

        $signature = $this->sanitizeOutgoingHtml($signature);

        if ($messageHtml !== '' && str_contains($messageHtml, $signature)) {
            return [
                'html' => $messageHtml,
                'text' => $this->htmlToText($messageHtml),
            ];
        }

        $separator = $messageHtml !== '' ? '<br><br><span style="color:#94a3b8">--</span><br>' : '';
        $html = $messageHtml . $separator . '<div class="ef-email-signature">' . $signature . '</div>';

        return [
            'html' => $html,
            'text' => $this->htmlToText($html),
        ];
    }

    /** @return array<int, array{id: int, email: string, name: string, signature_html: string|null, signature_auto: bool}> */
    public function signatureMapForAccounts(iterable $accounts): array
    {
        $map = [];

        foreach ($accounts as $account) {
            $map[$account->id] = [
                'id' => $account->id,
                'email' => $account->email,
                'name' => $account->name,
                'signature_html' => $account->signature_html,
                'signature_auto' => (bool) $account->signature_auto,
            ];
        }

        return $map;
    }
}
