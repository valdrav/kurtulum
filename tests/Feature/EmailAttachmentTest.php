<?php

namespace Tests\Feature;

use App\Models\Email;
use App\Models\EmailAccount;
use App\Models\EmailAttachment;
use Illuminate\Support\Facades\Storage;
use Tests\FeatureTestCase;

class EmailAttachmentTest extends FeatureTestCase
{
    public function test_user_can_download_own_email_attachment(): void
    {
        Storage::fake('local');

        $this->actingAsAdmin();
        $user = auth()->user();

        $account = EmailAccount::create([
            'user_id' => $user->id,
            'email' => 'test@kurtulum.com',
            'name' => 'Test',
            'provider' => 'plesk',
            'imap_host' => 'mail.kurtulum.com',
            'imap_port' => 993,
            'is_default' => true,
            'is_active' => true,
        ]);

        $email = Email::create([
            'email_account_id' => $account->id,
            'message_id' => 'test-msg-1',
            'subject' => 'Ekli mail',
            'from_email' => 'sender@example.com',
            'direction' => 'inbound',
            'received_at' => now(),
        ]);

        $path = 'email-attachments/' . $account->id . '/' . $email->id . '/test.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 test');

        $attachment = EmailAttachment::create([
            'email_id' => $email->id,
            'part_key' => '2',
            'filename' => 'fatura.pdf',
            'mime_type' => 'application/pdf',
            'size' => 12,
            'storage_path' => $path,
        ]);

        $this->get(route('emails.attachments.download', $attachment))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->get(route('emails.show', $email))
            ->assertOk()
            ->assertSee('fatura.pdf')
            ->assertSee(__('emails.attachments'));
    }
}
