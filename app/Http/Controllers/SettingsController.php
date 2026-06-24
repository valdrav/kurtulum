<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SiteBrandingService;
use App\Services\UpdateService;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.view')->only(['index', 'auditLog', 'updates', 'company', 'branding', 'security', 'mail', 'ai', 'marinetraffic']);
        $this->middleware('permission:settings.edit')->only(['update', 'updateBranding', 'updateSecurity', 'updateMail', 'updateAi', 'updateMarinetraffic', 'applyUpdate']);
    }

    public function index()
    {
        $stats = [
            'users' => \App\Models\User::count(),
            'employees' => \App\Models\Employee::count(),
            'languages' => \App\Models\SystemLanguage::count(),
            'modules' => \App\Models\SystemModule::where('is_enabled', true)->count(),
        ];

        return view('settings.index', compact('stats'));
    }

    public function company()
    {
        $settings = [
            'company_name' => Setting::get('company_name', config('app.name')),
            'company_email' => Setting::get('company_email'),
            'company_phone' => Setting::get('company_phone'),
            'company_address' => Setting::get('company_address'),
            'company_tax_number' => Setting::get('company_tax_number'),
            'company_tax_office' => Setting::get('company_tax_office'),
            'company_website' => Setting::get('company_website'),
            'default_currency' => Setting::get('default_currency', 'TRY'),
            'timezone' => Setting::get('timezone', 'Europe/Istanbul'),
        ];

        return view('settings.company', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_email' => 'nullable|email',
            'company_phone' => 'nullable|string|max:50',
            'company_address' => 'nullable|string|max:500',
            'company_tax_number' => 'nullable|string|max:50',
            'company_tax_office' => 'nullable|string|max:100',
            'company_website' => 'nullable|url|max:255',
            'default_currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:64',
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                Setting::set($key, $value, 'company');
            }
        }

        return back()->with('success', __('messages.saved'));
    }

    public function branding(SiteBrandingService $branding)
    {
        return view('settings.branding', ['settings' => $branding->formValues()]);
    }

    public function updateBranding(Request $request, SiteBrandingService $branding)
    {
        $validated = $request->validate([
            'site_short_name' => 'nullable|string|max:32',
            'site_tagline' => 'nullable|string|max:160',
            'site_meta_description' => 'nullable|string|max:320',
            'site_footer_text' => 'nullable|string|max:255',
            'site_theme_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => 'nullable|file|max:4096|mimetypes:image/jpeg,image/png,image/webp,image/svg+xml',
            'logo_dark' => 'nullable|file|max:4096|mimetypes:image/jpeg,image/png,image/webp,image/svg+xml',
            'favicon' => 'nullable|file|max:1024|mimetypes:image/jpeg,image/png,image/webp,image/x-icon,image/vnd.microsoft.icon',
            'apple_icon' => 'nullable|file|max:2048|mimetypes:image/jpeg,image/png,image/webp',
            'pwa_192' => 'nullable|file|max:2048|mimetypes:image/jpeg,image/png,image/webp',
            'pwa_512' => 'nullable|file|max:4096|mimetypes:image/jpeg,image/png,image/webp',
            'remove_logo' => 'nullable|boolean',
            'remove_logo_dark' => 'nullable|boolean',
            'remove_favicon' => 'nullable|boolean',
            'remove_apple_icon' => 'nullable|boolean',
            'remove_pwa_192' => 'nullable|boolean',
            'remove_pwa_512' => 'nullable|boolean',
        ]);

        foreach (SiteBrandingService::ASSET_FIELDS as $field => $settingKey) {
            if ($request->boolean('remove_' . $field)) {
                $branding->removeAsset($field);
            }
        }

        foreach (SiteBrandingService::ASSET_FIELDS as $field => $settingKey) {
            if ($request->hasFile($field)) {
                $branding->storeAsset($field, $request->file($field));
            }
        }

        $branding->saveTextSettings($validated);

        return back()->with('success', __('messages.saved'));
    }

    public function security()
    {
        $settings = [
            'session_lifetime' => Setting::get('session_lifetime', '120'),
            'password_min_length' => Setting::get('password_min_length', '8'),
            'require_strong_password' => Setting::get('require_strong_password', '0'),
            'login_attempts' => Setting::get('login_attempts', '5'),
            'lockout_minutes' => Setting::get('lockout_minutes', '15'),
            'force_single_session' => Setting::get('force_single_session', '0'),
            'audit_retention_days' => Setting::get('audit_retention_days', '365'),
        ];

        return view('settings.security', compact('settings'));
    }

    public function updateSecurity(Request $request)
    {
        $validated = $request->validate([
            'session_lifetime' => 'required|integer|min:15|max:1440',
            'password_min_length' => 'required|integer|min:6|max:32',
            'login_attempts' => 'required|integer|min:3|max:20',
            'lockout_minutes' => 'required|integer|min:1|max:120',
            'audit_retention_days' => 'required|integer|min:30|max:3650',
        ]);

        Setting::set('session_lifetime', $validated['session_lifetime'], 'security');
        Setting::set('password_min_length', $validated['password_min_length'], 'security');
        Setting::set('login_attempts', $validated['login_attempts'], 'security');
        Setting::set('lockout_minutes', $validated['lockout_minutes'], 'security');
        Setting::set('audit_retention_days', $validated['audit_retention_days'], 'security');
        Setting::set('require_strong_password', $request->boolean('require_strong_password') ? '1' : '0', 'security');
        Setting::set('force_single_session', $request->boolean('force_single_session') ? '1' : '0', 'security');

        return back()->with('success', __('messages.saved'));
    }

    public function mail()
    {
        $settings = [
            'mail_mailer' => Setting::get('mail_mailer', 'smtp'),
            'mail_host' => Setting::get('mail_host'),
            'mail_port' => Setting::get('mail_port', '587'),
            'mail_username' => Setting::get('mail_username'),
            'mail_encryption' => Setting::get('mail_encryption', 'tls'),
            'mail_from_address' => Setting::get('mail_from_address'),
            'mail_from_name' => Setting::get('mail_from_name', config('app.name')),
            'mail_imap_host' => Setting::get('mail_imap_host'),
            'mail_imap_port' => Setting::get('mail_imap_port', '993'),
            'mail_imap_encryption' => Setting::get('mail_imap_encryption', 'ssl'),
        ];

        return view('settings.mail', compact('settings'));
    }

    public function updateMail(Request $request)
    {
        $validated = $request->validate([
            'mail_mailer' => 'required|in:smtp,log',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|in:tls,ssl,null',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'mail_imap_host' => 'nullable|string|max:255',
            'mail_imap_port' => 'nullable|integer|min:1|max:65535',
            'mail_imap_encryption' => 'nullable|in:tls,ssl,null',
        ]);

        foreach ([
            'mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_encryption',
            'mail_from_address', 'mail_from_name', 'mail_imap_host', 'mail_imap_port', 'mail_imap_encryption',
        ] as $key) {
            if (array_key_exists($key, $validated) && $validated[$key] !== null) {
                Setting::set($key, $validated[$key], 'mail');
            }
        }

        if ($request->filled('mail_password')) {
            Setting::set('mail_password', $validated['mail_password'], 'mail');
        }

        return back()->with('success', __('messages.saved'));
    }

    public function ai()
    {
        $settings = [
            'ai_enabled' => Setting::get('ai_enabled', '1'),
            'ai_provider' => Setting::get('ai_provider', config('ticari.ai.provider')),
            'ai_model' => Setting::get('ai_model', config('ticari.ai.model')),
            'ai_api_key' => Setting::get('ai_api_key') ? '********' : '',
        ];

        return view('settings.ai', compact('settings'));
    }

    public function updateAi(Request $request)
    {
        $validated = $request->validate([
            'ai_provider' => 'required|in:openai,anthropic,custom',
            'ai_model' => 'required|string|max:100',
            'ai_api_key' => 'nullable|string|max:500',
        ]);

        Setting::set('ai_enabled', $request->boolean('ai_enabled') ? '1' : '0', 'ai');
        Setting::set('ai_provider', $validated['ai_provider'], 'ai');
        Setting::set('ai_model', $validated['ai_model'], 'ai');

        if ($request->filled('ai_api_key') && $validated['ai_api_key'] !== '********') {
            Setting::set('ai_api_key', $validated['ai_api_key'], 'ai');
        }

        return back()->with('success', __('messages.saved'));
    }

    public function marinetraffic()
    {
        $marinesiaKey = Setting::get('marinesia_api_key') ?: config('ticari.vessel_tracking.marinesia.api_key');
        $mtKey = Setting::get('marinetraffic_api_key') ?: config('ticari.marinetraffic.api_key');

        $settings = [
            'marinesia_api_key' => Setting::get('marinesia_api_key') ? '********' : '',
            'marinetraffic_api_key' => Setting::get('marinetraffic_api_key') ? '********' : '',
            'configured' => (bool) ($marinesiaKey || $mtKey),
            'provider' => $marinesiaKey ? 'marinesia' : ($mtKey ? 'marinetraffic' : null),
        ];

        return view('settings.marinetraffic', compact('settings'));
    }

    public function updateMarinetraffic(Request $request)
    {
        $validated = $request->validate([
            'marinesia_api_key' => 'nullable|string|max:128',
            'marinetraffic_api_key' => 'nullable|string|max:64',
        ]);

        if ($request->filled('marinesia_api_key') && $validated['marinesia_api_key'] !== '********') {
            Setting::set('marinesia_api_key', trim($validated['marinesia_api_key']), 'integrations');
        }

        if ($request->filled('marinetraffic_api_key') && $validated['marinetraffic_api_key'] !== '********') {
            Setting::set('marinetraffic_api_key', trim($validated['marinetraffic_api_key']), 'integrations');
        }

        return back()->with('success', __('messages.saved'));
    }

    public function auditLog()
    {
        $logs = Activity::with('causer')->latest()->paginate(30);
        return view('settings.audit-log', compact('logs'));
    }

    public function updates(UpdateService $updateService)
    {
        $updateInfo = $updateService->checkForUpdates();
        return view('settings.updates', compact('updateInfo'));
    }

    public function applyUpdate(Request $request, UpdateService $updateService)
    {
        $request->validate(['package' => 'required|file|mimes:zip|max:51200']);
        $path = $request->file('package')->store('updates');
        $result = $updateService->applyUpdate(storage_path('app/' . $path));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
