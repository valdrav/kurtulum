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

    public function syncAccount(EmailAccount $account, int $limit = 30): int
    {
        if (! $this->isAvailable()) {
            throw new \RuntimeException('PHP IMAP eklentisi sunucuda aktif değil. Plesk/cPanel üzerinden php-imap etkinleştirin.');
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
            throw new \RuntimeException('IMAP bağlantısı kurulamadı: ' . imap_last_error());
        }

        $synced = 0;
        $emails = imap_search($connection, 'ALL') ?: [];
        rsort($emails);
        $emails = array_slice($emails, 0, $limit);

        foreach ($emails as $msgNo) {
            $header = imap_headerinfo($connection, $msgNo);
            $messageId = $header->message_id ?? ('local-' . $account->id . '-' . $msgNo);

            if (Email::where('message_id', $messageId)->exists()) {
                continue;
            }

            $structure = imap_fetchstructure($connection, $msgNo);
            $bodyText = $this->fetchBody($connection, $msgNo, $structure, 'plain');
            $bodyHtml = $this->fetchBody($connection, $msgNo, $structure, 'html');

            Email::create([
                'email_account_id' => $account->id,
                'message_id' => $messageId,
                'subject' => isset($header->subject) ? imap_utf8($header->subject) : '(Konu yok)',
                'body_text' => $bodyText,
                'body_html' => $bodyHtml,
                'from_email' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
                'from_name' => $header->from[0]->personal ?? null,
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
            return imap_body($connection, $msgNo);
        }

        if ($structure->type === 0 && ($type === 'plain' || empty($structure->parts))) {
            return imap_body($connection, $msgNo);
        }

        if (empty($structure->parts)) {
            return imap_body($connection, $msgNo);
        }

        foreach ($structure->parts as $i => $part) {
            $partNo = (string) ($i + 1);
            $subtype = strtolower($part->subtype ?? '');

            if (($type === 'plain' && $subtype === 'plain') || ($type === 'html' && $subtype === 'html')) {
                $body = imap_fetchbody($connection, $msgNo, $partNo);
                if ($part->encoding == 3) {
                    $body = base64_decode($body);
                } elseif ($part->encoding == 4) {
                    $body = quoted_printable_decode($body);
                }

                return $body ?: null;
            }
        }

        return null;
    }

    protected function parseAddresses(array $addresses): array
    {
        $result = [];
        foreach ($addresses as $addr) {
            $result[] = $addr->mailbox . '@' . $addr->host;
        }

        return $result;
    }
}
