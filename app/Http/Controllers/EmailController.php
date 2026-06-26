<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\EmailAccount;
use App\Models\EmailAttachment;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\EmailSignatureService;
use App\Services\ImapMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EmailController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:emails.view')->only(['index', 'show', 'accounts', 'signatures', 'downloadAttachment']);
        $this->middleware('permission:emails.create')->only(['storeAccount', 'compose', 'send', 'sync']);
        $this->middleware('permission:emails.edit|emails.create')->only(['editAccount', 'updateAccount', 'updateSignature']);
        $this->middleware('permission:emails.delete|emails.create')->only(['destroyAccount']);
    }

    protected function userAccounts()
    {
        return EmailAccount::where('user_id', auth()->id())->where('is_active', true);
    }

    protected function userAccountIds(): array
    {
        return $this->userAccounts()->pluck('id')->all();
    }

    protected function authorizeAccount(EmailAccount $account): void
    {
        abort_unless($account->user_id === auth()->id(), 403);
    }

    protected function validatedAccountData(Request $request, bool $updating = false): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'provider' => 'required|in:plesk,microsoft365,google,yandex,custom',
            'smtp_host' => 'nullable|string',
            'smtp_port' => 'nullable|integer',
            'smtp_encryption' => 'nullable|string|in:ssl,tls,none',
            'imap_host' => 'nullable|string',
            'imap_port' => 'nullable|integer',
            'imap_encryption' => 'nullable|string|in:ssl,tls,none',
            'smtp_username' => 'nullable|string',
            'smtp_password' => $updating ? 'nullable|string' : 'required|string|min:1',
            'signature_html' => 'nullable|string|max:15000',
            'signature_auto' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $validated = EmailAccount::applyProviderDefaults($validated);

        if ($validated['provider'] === 'custom') {
            $request->validate([
                'imap_host' => 'required|string',
                'smtp_host' => 'required|string',
            ]);
        }

        $validated['signature_auto'] = $request->boolean('signature_auto', true);

        return $validated;
    }

    protected function persistAccount(EmailAccount $account, array $validated, Request $request, bool $updating = false): void
    {
        if ($request->boolean('is_default')) {
            EmailAccount::where('user_id', auth()->id())
                ->when($updating, fn ($q) => $q->where('id', '!=', $account->id))
                ->update(['is_default' => false]);
        }

        $account->fill(collect($validated)->except(['smtp_username', 'smtp_password'])->toArray());

        if ($updating) {
            $account->syncCredentials(
                $validated['smtp_username'] ?? $validated['email'],
                $validated['smtp_password'] ?? null
            );
        } else {
            $account->user_id = auth()->id();
            $account->setCredentialsFromRequest(
                $validated['smtp_username'] ?? $validated['email'],
                $validated['smtp_password']
            );
        }

        $imap = app(ImapMailService::class);
        if ($imap->isAvailable() && $account->imap_host) {
            $imap->testConnection($account);
        }

        $account->save();
    }

    public function index(Request $request, ImapMailService $imap)
    {
        $accountIds = $this->userAccountIds();
        $imapAvailable = $imap->isAvailable();
        $syncMessage = null;

        if ($imapAvailable && ! $request->filled('page') && ! $request->boolean('no_sync')) {
            try {
                $total = 0;
                foreach ($this->userAccounts()->get() as $account) {
                    $total += $imap->syncAccount($account);
                }
                if ($total > 0) {
                    $syncMessage = __('emails.sync_success', ['count' => $total]);
                }
            } catch (\Throwable $e) {
                $syncMessage = null;
            }
        }

        $emails = Email::with('emailAccount')
            ->withCount('attachments')
            ->whereIn('email_account_id', $accountIds)
            ->when($request->account, fn ($q, $id) => $q->where('email_account_id', $id))
            ->when($request->folder === 'starred', fn ($q) => $q->where('is_starred', true))
            ->when($request->folder === 'unread', fn ($q) => $q->where('is_read', false))
            ->when($request->direction === 'inbound', fn ($q) => $q->where('direction', 'inbound'))
            ->when($request->direction === 'outbound', fn ($q) => $q->where('direction', 'outbound'))
            ->when($request->q, function ($q, $search) {
                $term = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($search)) . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('subject', 'like', $term)
                        ->orWhere('from_email', 'like', $term)
                        ->orWhere('from_name', 'like', $term)
                        ->orWhere('body_text', 'like', $term);
                });
            })
            ->when($request->date_from, fn ($q, $d) => $q->whereRaw('COALESCE(received_at, sent_at) >= ?', [$d]))
            ->when($request->date_to, fn ($q, $d) => $q->whereRaw('COALESCE(received_at, sent_at) <= ?', [$d . ' 23:59:59']))
            ->latest('received_at')
            ->latest('sent_at')
            ->paginate(25)
            ->withQueryString();

        $accounts = $this->userAccounts()->get();

        return view('emails.index', compact('emails', 'accounts', 'imapAvailable', 'syncMessage'));
    }

    public function show(Email $email)
    {
        abort_unless(in_array($email->email_account_id, $this->userAccountIds()), 403);

        if (! $email->is_read) {
            $email->update(['is_read' => true]);
        }

        $email->load(['emailAccount', 'attachments']);

        if ($email->direction === 'inbound' && $email->attachments->isEmpty()) {
            $imap = app(ImapMailService::class);

            if ($imap->isAvailable()) {
                try {
                    $imap->refreshAttachmentsForEmail($email);
                    $email->load('attachments');
                } catch (\Throwable) {
                    // IMAP hatası mail gövdesini engellemesin
                }
            }
        }

        return view('emails.show', compact('email'));
    }

    public function downloadAttachment(EmailAttachment $attachment)
    {
        $attachment->load('email');
        abort_unless(in_array($attachment->email->email_account_id, $this->userAccountIds()), 403);

        if (! Storage::disk('local')->exists($attachment->storage_path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $attachment->storage_path,
            $attachment->filename
        );
    }

    public function accounts()
    {
        $accounts = EmailAccount::where('user_id', auth()->id())->latest()->get();
        $presets = EmailAccount::providerPresets();
        $imapAvailable = app(ImapMailService::class)->isAvailable();

        return view('emails.accounts', compact('accounts', 'presets', 'imapAvailable'));
    }

    public function editAccount(EmailAccount $account)
    {
        $this->authorizeAccount($account);

        $imapAvailable = app(ImapMailService::class)->isAvailable();

        return view('emails.account-edit', compact('account', 'imapAvailable'));
    }

    public function storeAccount(Request $request)
    {
        $validated = $this->validatedAccountData($request);

        $account = new EmailAccount;

        try {
            $this->persistAccount($account, $validated, $request);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['imap' => $e->getMessage()]);
        }

        return redirect()->route('emails.accounts')->with('success', __('messages.created'));
    }

    public function updateAccount(Request $request, EmailAccount $account)
    {
        $this->authorizeAccount($account);

        $validated = $this->validatedAccountData($request, updating: true);

        try {
            $this->persistAccount($account, $validated, $request, updating: true);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['imap' => $e->getMessage()]);
        }

        return redirect()->route('emails.accounts')->with('success', __('messages.updated'));
    }

    public function destroyAccount(EmailAccount $account)
    {
        $this->authorizeAccount($account);

        $account->delete();

        return redirect()->route('emails.accounts')->with('success', __('messages.deleted'));
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

        return back()->with('success', __('emails.sync_success', ['count' => $total]));
    }

    public function signatures()
    {
        $accounts = EmailAccount::where('user_id', auth()->id())->orderBy('email')->get();

        return view('emails.signatures', compact('accounts'));
    }

    public function updateSignature(Request $request, EmailAccount $account)
    {
        $this->authorizeAccount($account);

        $validated = $request->validate([
            'signature_html' => 'nullable|string|max:15000',
            'signature_auto' => 'boolean',
        ]);

        $account->update([
            'signature_html' => $validated['signature_html'] ?? '',
            'signature_auto' => $request->boolean('signature_auto'),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => __('messages.saved')]);
        }

        return back()->with('success', __('messages.saved'));
    }

    public function compose(Request $request, EmailSignatureService $signatures)
    {
        $accounts = $this->userAccounts()->get();
        $signatureMap = $signatures->signatureMapForAccounts($accounts);
        $defaultAccountId = $accounts->firstWhere('is_default', true)?->id ?? $accounts->first()?->id;

        $orders = Order::with('customer:id,company_name')
            ->latest('order_date')->limit(50)->get(['id', 'order_number', 'customer_id']);
        $shipments = Shipment::latest()->limit(50)->get(['id', 'shipment_number', 'order_id']);

        $linkType = $request->input('link_type');
        $linkId = $request->input('link_id');

        return view('emails.compose', compact(
            'accounts', 'signatureMap', 'defaultAccountId', 'orders', 'shipments', 'linkType', 'linkId'
        ));
    }

    public function send(Request $request, EmailSignatureService $signatures)
    {
        $validated = $request->validate([
            'email_account_id' => 'required|exists:email_accounts,id',
            'to_emails' => 'required|string',
            'subject' => 'required|string|max:500',
            'body_html' => 'required|string',
            'include_signature' => 'boolean',
            'emailable_type' => 'nullable|string',
            'emailable_id' => 'nullable|integer',
        ]);

        $account = EmailAccount::where('user_id', auth()->id())
            ->findOrFail($validated['email_account_id']);

        $composed = $signatures->buildOutgoingBody(
            $validated['body_html'],
            $account,
            $request->boolean('include_signature', true)
        );

        if (trim(strip_tags($composed['html'])) === '') {
            return back()->withInput()->withErrors(['body_html' => __('emails.body_required')]);
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $account->smtp_host,
            'mail.mailers.smtp.port' => $account->smtp_port,
            'mail.mailers.smtp.encryption' => $account->smtp_encryption === 'none' ? null : $account->smtp_encryption,
            'mail.mailers.smtp.username' => $account->smtpUsername(),
            'mail.mailers.smtp.password' => $account->smtpPassword(),
        ]);

        try {
            Mail::html($composed['html'], function ($message) use ($validated, $account, $composed) {
                $message->from($account->email, $account->name)
                    ->to(array_map('trim', explode(',', $validated['to_emails'])))
                    ->subject($validated['subject']);

                $message->text($composed['text']);
            });

            Email::create([
                'email_account_id' => $account->id,
                'direction' => 'outbound',
                'from_email' => $account->email,
                'from_name' => $account->name,
                'to' => array_map('trim', explode(',', $validated['to_emails'])),
                'subject' => $validated['subject'],
                'body_html' => $composed['html'],
                'body_text' => $composed['text'],
                'sent_at' => now(),
                'emailable_type' => $validated['emailable_type'] ?? null,
                'emailable_id' => $validated['emailable_id'] ?? null,
            ]);

            return redirect()->route('emails.index')->with('success', __('emails.sent'));
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['send' => $e->getMessage()]);
        }
    }
}
