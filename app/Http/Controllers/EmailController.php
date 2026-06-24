<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\EmailAccount;
use App\Services\ImapMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:emails.view')->only(['index', 'show', 'accounts']);
        $this->middleware('permission:emails.create')->only(['storeAccount', 'compose', 'send', 'sync']);
    }

    protected function userAccounts()
    {
        return EmailAccount::where('user_id', auth()->id())->where('is_active', true);
    }

    protected function userAccountIds(): array
    {
        return $this->userAccounts()->pluck('id')->all();
    }

    public function index(Request $request)
    {
        $accountIds = $this->userAccountIds();

        $emails = Email::with('emailAccount')
            ->whereIn('email_account_id', $accountIds)
            ->when($request->account, fn ($q, $id) => $q->where('email_account_id', $id))
            ->when($request->folder === 'starred', fn ($q) => $q->where('is_starred', true))
            ->when($request->folder === 'unread', fn ($q) => $q->where('is_read', false))
            ->latest('received_at')
            ->latest('sent_at')
            ->paginate(25);

        $accounts = $this->userAccounts()->get();
        $imapAvailable = app(ImapMailService::class)->isAvailable();

        return view('emails.index', compact('emails', 'accounts', 'imapAvailable'));
    }

    public function show(Email $email)
    {
        abort_unless(in_array($email->email_account_id, $this->userAccountIds()), 403);

        if (! $email->is_read) {
            $email->update(['is_read' => true]);
        }

        $email->load('emailAccount');

        return view('emails.show', compact('email'));
    }

    public function accounts()
    {
        $accounts = EmailAccount::where('user_id', auth()->id())->latest()->get();
        $presets = EmailAccount::providerPresets();
        $imapAvailable = app(ImapMailService::class)->isAvailable();

        return view('emails.accounts', compact('accounts', 'presets', 'imapAvailable'));
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'provider' => 'required|in:smtp,microsoft365,google,yandex,custom',
            'smtp_host' => 'nullable|string',
            'smtp_port' => 'nullable|integer',
            'smtp_encryption' => 'nullable|string|in:ssl,tls,none',
            'imap_host' => 'nullable|string',
            'imap_port' => 'nullable|integer',
            'imap_encryption' => 'nullable|string|in:ssl,tls,none',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        if (in_array($validated['provider'], ['microsoft365', 'google', 'yandex'], true)) {
            $validated = array_merge(EmailAccount::providerPresets()[$validated['provider']], $validated);
        }

        if ($request->boolean('is_default')) {
            EmailAccount::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $account = new EmailAccount(collect($validated)->except(['smtp_username', 'smtp_password'])->toArray());
        $account->user_id = auth()->id();
        $account->setCredentialsFromRequest(
            $validated['smtp_username'] ?? $validated['email'],
            $validated['smtp_password'] ?? null
        );
        $account->save();

        return back()->with('success', __('messages.created'));
    }

    public function sync(Request $request, ImapMailService $imap)
    {
        $accountId = $request->input('account_id');
        $query = EmailAccount::where('user_id', auth()->id())->where('is_active', true);

        if ($accountId) {
            $query->where('id', $accountId);
        }

        $total = 0;
        $errors = [];

        foreach ($query->get() as $account) {
            try {
                $total += $imap->syncAccount($account);
            } catch (\Throwable $e) {
                $errors[] = $account->email . ': ' . $e->getMessage();
            }
        }

        if ($errors && $total === 0) {
            return back()->withErrors(['sync' => implode(' | ', $errors)]);
        }

        return back()->with('success', "{$total} yeni mesaj alındı.");
    }

    public function compose()
    {
        $accounts = $this->userAccounts()->get();

        return view('emails.compose', compact('accounts'));
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'email_account_id' => 'required|exists:email_accounts,id',
            'to_emails' => 'required|string',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'emailable_type' => 'nullable|string',
            'emailable_id' => 'nullable|integer',
        ]);

        $account = EmailAccount::where('user_id', auth()->id())
            ->findOrFail($validated['email_account_id']);

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $account->smtp_host,
            'mail.mailers.smtp.port' => $account->smtp_port,
            'mail.mailers.smtp.encryption' => $account->smtp_encryption === 'none' ? null : $account->smtp_encryption,
            'mail.mailers.smtp.username' => $account->smtpUsername(),
            'mail.mailers.smtp.password' => $account->smtpPassword(),
        ]);

        try {
            Mail::raw($validated['body'], function ($message) use ($validated, $account) {
                $message->from($account->email, $account->name)
                    ->to(array_map('trim', explode(',', $validated['to_emails'])))
                    ->subject($validated['subject']);
            });

            Email::create([
                'email_account_id' => $account->id,
                'direction' => 'outbound',
                'from_email' => $account->email,
                'from_name' => $account->name,
                'to' => array_map('trim', explode(',', $validated['to_emails'])),
                'subject' => $validated['subject'],
                'body_text' => $validated['body'],
                'sent_at' => now(),
                'emailable_type' => $validated['emailable_type'] ?? null,
                'emailable_id' => $validated['emailable_id'] ?? null,
            ]);

            return redirect()->route('emails.index')->with('success', 'E-posta gönderildi.');
        } catch (\Exception $e) {
            return back()->withErrors(['send' => $e->getMessage()]);
        }
    }
}
