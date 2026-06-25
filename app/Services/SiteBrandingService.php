<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteBrandingService
{
    public const GROUP = 'branding';

    /** @var array<string, string> */
    public const ASSET_FIELDS = [
        'logo' => 'site_logo',
        'logo_dark' => 'site_logo_dark',
        'favicon' => 'site_favicon',
        'apple_icon' => 'site_apple_icon',
        'pwa_192' => 'site_pwa_icon_192',
        'pwa_512' => 'site_pwa_icon_512',
    ];

    /** @var array<string, string> */
    public const TEXT_FIELDS = [
        'site_short_name',
        'site_tagline',
        'site_meta_description',
        'site_footer_text',
        'site_theme_color',
    ];

    public function shortName(): string
    {
        $short = trim((string) $this->get('site_short_name', ''));

        if ($short !== '') {
            return $short;
        }

        $company = Setting::get('company_name');

        if ($company) {
            return Str::limit($company, 24, '');
        }

        return config('app.name', 'Kurtulum İç ve Dış Ticaret');
    }

    public function tagline(): string
    {
        return trim((string) $this->get('site_tagline', 'İhracat, Lojistik ve Finans Yönetim Sistemi'));
    }

    public function metaDescription(): string
    {
        $desc = trim((string) $this->get('site_meta_description', ''));

        return $desc !== '' ? $desc : $this->tagline();
    }

    public function footerText(): ?string
    {
        $text = trim((string) $this->get('site_footer_text', ''));

        return $text !== '' ? $text : null;
    }

    public function themeColor(): string
    {
        $color = trim((string) $this->get('site_theme_color', ''));

        if ($color !== '' && preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return $color;
        }

        return '#6366f1';
    }

    public function themeColorRgb(): string
    {
        return $this->hexToRgb($this->themeColor());
    }

    public function themeColorDark(): string
    {
        return $this->darkenHex($this->themeColor(), 0.12);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }

    public function assetUrl(string $settingKey, ?string $fallback = null): string
    {
        $path = trim((string) Setting::get($settingKey, ''));

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            return $this->publicAssetUrl($path);
        }

        return $fallback ? asset($fallback) : '';
    }

    public function logoUrl(bool $dark = false): ?string
    {
        $key = $dark ? 'site_logo_dark' : 'site_logo';
        $url = $this->assetUrl($key);

        if ($url !== '') {
            return $url;
        }

        if ($dark) {
            return $this->logoUrl(false);
        }

        return null;
    }

    public function faviconUrl(): string
    {
        return $this->assetUrl('site_favicon', 'icons/icon.svg');
    }

    public function appleIconUrl(): string
    {
        $url = $this->assetUrl('site_apple_icon');

        if ($url !== '') {
            return $url;
        }

        return $this->assetUrl('site_pwa_icon_192', 'icons/icon.svg');
    }

    public function pwaIcon192Url(): string
    {
        return $this->assetUrl('site_pwa_icon_192', 'icons/icon.svg');
    }

    public function pwaIcon512Url(): string
    {
        return $this->assetUrl('site_pwa_icon_512', 'icons/icon.svg');
    }

    public function hasLogo(): bool
    {
        return trim((string) Setting::get('site_logo', '')) !== '';
    }

    /** @return array<string, mixed> */
    public function formValues(): array
    {
        $values = [];

        foreach (self::TEXT_FIELDS as $field) {
            $values[$field] = Setting::get($field, $this->defaultFor($field));
        }

        foreach (self::ASSET_FIELDS as $field => $settingKey) {
            $values["{$field}_url"] = $this->assetUrl($settingKey) ?: null;
            $values["has_{$field}"] = trim((string) Setting::get($settingKey, '')) !== '';
        }

        return $values;
    }

    /** @return array<string, mixed> */
    public function manifest(): array
    {
        return [
            'name' => app_brand(),
            'short_name' => $this->shortName(),
            'description' => $this->metaDescription(),
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => $this->themeColor(),
            'theme_color' => $this->themeColor(),
            'orientation' => 'any',
            'lang' => str_replace('_', '-', app()->getLocale()),
            'icons' => [
                [
                    'src' => $this->pwaIcon192Url(),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => $this->pwaIcon512Url(),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $validated */
    public function saveTextSettings(array $validated): void
    {
        foreach (self::TEXT_FIELDS as $field) {
            if (array_key_exists($field, $validated)) {
                Setting::set($field, $validated[$field] ?? '', self::GROUP);
            }
        }
    }

    public function storeAsset(string $field, UploadedFile $file): void
    {
        if (! isset(self::ASSET_FIELDS[$field])) {
            return;
        }

        $settingKey = self::ASSET_FIELDS[$field];
        $this->deleteStoredFile($settingKey);

        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = $field . '.' . $extension;
        $path = $file->storeAs('branding', $filename, 'public');

        Setting::set($settingKey, $path, self::GROUP);
    }

    public function removeAsset(string $field): void
    {
        if (! isset(self::ASSET_FIELDS[$field])) {
            return;
        }

        $this->deleteStoredFile(self::ASSET_FIELDS[$field]);
    }

    public function storeUserAvatar(User $user, UploadedFile $file): string
    {
        $this->deleteUserAvatar($user);

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $path = $file->storeAs('avatars/' . $user->uuid, 'avatar.' . $extension, 'public');

        $user->update(['avatar' => $path]);

        return $path;
    }

    public function deleteUserAvatar(User $user): void
    {
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);
    }

    public function userAvatarUrl(?User $user): ?string
    {
        if (! $user?->avatar) {
            return null;
        }

        if (! Storage::disk('public')->exists($user->avatar)) {
            return null;
        }

        return $this->publicAssetUrl($user->avatar);
    }

    public function publicAssetUrl(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        return url('/media/' . $path);
    }

    public function userInitials(?User $user): string
    {
        if (! $user?->name) {
            return '?';
        }

        $parts = preg_split('/\s+/u', trim($user->name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : mb_strtoupper(mb_substr($user->name, 0, 1));
    }

    protected function deleteStoredFile(string $settingKey): void
    {
        $existing = Setting::get($settingKey);

        if ($existing && Storage::disk('public')->exists($existing)) {
            Storage::disk('public')->delete($existing);
        }

        Setting::set($settingKey, '', self::GROUP);
    }

    protected function defaultFor(string $field): string
    {
        return match ($field) {
            'site_theme_color' => '#6366f1',
            'site_tagline' => 'İhracat, Lojistik ve Finans Yönetim Sistemi',
            default => '',
        };
    }

    protected function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
    }

    protected function darkenHex(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');
        $r = max(0, (int) round(hexdec(substr($hex, 0, 2)) * (1 - $amount)));
        $g = max(0, (int) round(hexdec(substr($hex, 2, 2)) * (1 - $amount)));
        $b = max(0, (int) round(hexdec(substr($hex, 4, 2)) * (1 - $amount)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
