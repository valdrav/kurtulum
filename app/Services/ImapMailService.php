<?php

namespace App\Services;

use App\Models\Email;
use App\Models\EmailAccount;

class ImapMailService
{
    public function isAvailable(): bool
    {
        return function_exists('imap_open');
    }

    public function testConnection(EmailAccount $account): void
    {
        $connection = $this->openConnection($account);
        imap_close($connection);
    }

    public function syncAccount(EmailAccount $account, int $limit = 30): int
    {
        $connection = $this->openConnection($account);

        $synced = 0;
        $emails = imap_search($connection, 'ALL') ?: [];
        rsort($emails);
        $emails = array_slice($emails, 0, $limit);

        foreach ($emails as $msgNo) {
            $header = imap_headerinfo($connection, $msgNo);
            $messageId = $header->message_id ?? ('local-' . $account->id . '-' . $msgNo);

            $structure = imap_fetchstructure($connection, $msgNo);
            $bodyText = $this->fetchBody($connection, $msgNo, $structure, 'plain');
            $bodyHtml = $this->fetchBody($connection, $msgNo, $structure, 'html');

            $existing = Email::where('message_id', $messageId)->first();

            if ($existing) {
                if ($this->shouldRepairBody($existing, $bodyText, $bodyHtml)) {
                    $existing->update([
                        'body_text' => $bodyText ?: $existing->body_text,
                        'body_html' => $bodyHtml ?: $existing->body_html,
                    ]);
                    $synced++;
                }

                continue;
            }

            Email::create([
                'email_account_id' => $account->id,
                'message_id' => $messageId,
                'subject' => isset($header->subject) ? imap_utf8($header->subject) : '(Konu yok)',
                'body_text' => $bodyText,
                'body_html' => $bodyHtml,
                'from_email' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
                'from_name' => isset($header->from[0]->personal) ? imap_utf8($header->from[0]->personal) : null,
                'to' => $this->parseAddresses($header->to ?? []),
                'received_at' => isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : now(),
                'direction' => 'inbound',
                'is_read' => ($header->Unseen ?? 'U') !== 'U',
            ]);

            $synced++;
        }

        imap_close($connection);

        return $synced;
    }

    protected function shouldRepairBody(Email $email, ?string $bodyText, ?string $bodyHtml): bool
    {
        if (! $bodyText && ! $bodyHtml) {
            return false;
        }

        $currentText = trim((string) $email->body_text);
        $currentHtml = trim(strip_tags((string) $email->body_html));

        if ($currentText === '' && $currentHtml === '') {
            return true;
        }

        if ($currentText !== '' && strlen($currentText) < 15 && $bodyText && strlen(trim($bodyText)) > strlen($currentText)) {
            return true;
        }

        if ($currentHtml === '' && $bodyHtml) {
            return true;
        }

        return false;
    }

    protected function buildMailboxString(EmailAccount $account): string
    {
        $host = $account->imap_host;
        $port = $account->imap_port ?: 993;
        $enc = $account->imap_encryption ?: 'ssl';

        $flags = match ($enc) {
            'ssl' => '/imap/ssl/novalidate-cert',
            'tls' => '/imap/tls/novalidate-cert',
            default => '/imap/notls',
        };

        return '{' . $host . ':' . $port . $flags . '}INBOX';
    }

    protected function fetchBody($connection, int $msgNo, $structure, string $type): ?string
    {
        if (! $structure) {
            return $this->decodeBody(imap_body($connection, $msgNo), null);
        }

        $body = $this->findPartBody($connection, $msgNo, $structure, $type, '');

        if ($body !== null) {
            return $body;
        }

        if (empty($structure->parts)) {
            $subtype = strtolower($structure->subtype ?? '');

            if (($type === 'plain' && $subtype === 'plain') || ($type === 'html' && $subtype === 'html')) {
                return $this->decodePart($connection, $msgNo, '1', $structure);
            }
        }

        return null;
    }

    protected function findPartBody($connection, int $msgNo, $structure, string $type, string $partNumber): ?string
    {
        if (($structure->type ?? 0) === 1 && ! empty($structure->parts)) {
            foreach ($structure->parts as $index => $part) {
                $subPart = $partNumber === '' ? (string) ($index + 1) : $partNumber . '.' . ($index + 1);
                $body = $this->findPartBody($connection, $msgNo, $part, $type, $subPart);

                if ($body !== null) {
                    return $body;
                }
            }

            return null;
        }

        $subtype = strtolower($structure->subtype ?? '');

        if ($type === 'plain' && $subtype !== 'plain') {
            return null;
        }

        if ($type === 'html' && $subtype !== 'html') {
            return null;
        }

        $fetchPart = $partNumber === '' ? '1' : $partNumber;

        return $this->decodePart($connection, $msgNo, $fetchPart, $structure);
    }

    protected function decodePart($connection, int $msgNo, string $partNumber, $structure): ?string
    {
        $raw = imap_fetchbody($connection, $msgNo, $partNumber);

        if ($raw === false || $raw === '') {
            return null;
        }

        return $this->decodeBody($raw, $structure);
    }

    protected function decodeBody(string $raw, $structure): ?string
    {
        $encoding = $structure->encoding ?? 0;

        $body = match ($encoding) {
            3 => base64_decode($raw, true) ?: $raw,
            4 => quoted_printable_decode($raw),
            default => $raw,
        };

        if (! is_string($body) || trim($body) === '') {
            return null;
        }

        $charset = 'UTF-8';

        if ($structure && ! empty($structure->parameters)) {
            foreach ($structure->parameters as $param) {
                if (strtolower($param->attribute ?? '') === 'charset') {
                    $charset = $param->value;
                    break;
                }
            }
        }

        if ($structure && ! empty($structure->dparameters)) {
            foreach ($structure->dparameters as $param) {
                if (strtolower($param->attribute ?? '') === 'charset') {
                    $charset = $param->value;
                    break;
                }
            }
        }

        if (strtoupper($charset) !== 'UTF-8' && function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($body, 'UTF-8', $charset);

            if ($converted !== false) {
                $body = $converted;
            }
        }

        return $body;
    }

    protected function parseAddresses(array $addresses): array
    {
        $result = [];
        foreach ($addresses as $addr) {
            $result[] = $addr->mailbox . '@' . $addr->host;
        }

        return $result;
    }

    protected function openConnection(EmailAccount $account)
    {
        if (! $this->isAvailable()) {
            throw new \RuntimeException('PHP IMAP eklentisi sunucuda aktif değil. Plesk/cPanel üzerinden php-imap etkinleştirin.');
        }

        if (! $account->imap_host) {
            throw new \RuntimeException('IMAP sunucusu tanımlı değil. Plesk Mail veya Özel sunucu seçin.');
        }

        $creds = $account->getCredentials();
        $username = $creds['username'] ?? $account->email;
        $password = $creds['password'] ?? '';

        if ($password === '') {
            throw new \RuntimeException('E-posta hesabı için şifre tanımlı değil.');
        }

        $mailbox = $this->buildMailboxString($account);
        $connection = @imap_open($mailbox, $username, $password, 0, 1);

        if (! $connection) {
            throw new \RuntimeException($this->formatConnectionError($account, imap_last_error() ?: 'Bilinmeyen hata'));
        }

        return $connection;
    }

    protected function formatConnectionError(EmailAccount $account, string $error): string
    {
        $base = 'IMAP bağlantısı kurulamadı: ' . $error;

        if (! str_contains(strtoupper($error), 'AUTHENTICATIONFAILED')) {
            return $base;
        }

        $hints = [
            'Kullanıcı adı genelde tam e-posta adresidir (örn. omer@kurtulum.com).',
            'Şifre: Plesk → Posta → ilgili posta kutusunun şifresi (portal giriş şifresi değil).',
        ];

        if ($account->provider === 'plesk') {
            $domain = substr(strrchr($account->email, '@'), 1);
            $hints[] = "Sunucu: {$account->imap_host} (Plesk'te genelde {$domain} veya mail.{$domain}) — formda IMAP/SMTP alanlarını Plesk'teki gibi girin.";
        } elseif ($account->provider === 'microsoft365') {
            $hints[] = '@kurtulum.com gibi domain mailleri Microsoft 365 değil; sağlayıcı olarak Plesk Mail seçin.';
        } elseif ($account->provider === 'google') {
            $hints[] = 'Gmail için Google hesabında 2 adımlı doğrulama + uygulama şifresi gerekir.';
        }

        return $base . ' — ' . implode(' ', $hints);
    }
}
