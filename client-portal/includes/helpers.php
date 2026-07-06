<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: '.$url);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (! is_string($token) || ! hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Geçersiz oturum. Sayfayı yenileyip tekrar deneyin.');
    }
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;

        return null;
    }

    $msg = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $msg;
}

function app_name(array $config): string
{
    return $config['app_name'] ?? 'Portal';
}

function require_login(): void
{
    if (empty($_SESSION['user'])) {
        redirect('login.php');
    }
}

function api_permissions(): array
{
    return $_SESSION['api_permissions'] ?? [];
}

function can(string $permission): bool
{
    return ! empty(api_permissions()[$permission]);
}

function refresh_api_context(ApiClient $api): ?string
{
    $response = $api->get('/me');

    if ($response['status'] === 200 && is_array($response['body'])) {
        $_SESSION['api_me'] = $response['body'];
        $_SESSION['api_permissions'] = $response['body']['permissions'] ?? [];

        return null;
    }

    return describe_api_failure($api, $response);
}

/** @param array{status: int, body: mixed, error: ?string, raw: ?string} $response */
function describe_api_failure(ApiClient $api, array $response): string
{
    $url = $api->baseUrl().'/me';

    if ($response['status'] === 0) {
        return 'Ana sisteme ulaşılamadı: '.($response['error'] ?? 'bağlantı hatası')
            .'. Adres: '.$url;
    }

    if ($response['status'] === 503) {
        return 'Harici API ana sistemde kapalı. Ticari → Ayarlar → Harici Sistem API → "Harici API etkin" işaretleyip kaydedin.';
    }

    if ($response['status'] === 401) {
        return 'API anahtarı reddedildi (401). config.php içindeki api_token, Ayarlar\'da oluşturduğunuz ef_... anahtarı ile aynı olmalı.';
    }

    if ($response['status'] === 404) {
        return 'API adresi bulunamadı (404): '.$url
            .'. XAMPP için genelde: http://localhost/ticari/public/api/v1';
    }

    if ($response['status'] >= 500) {
        return 'Ana sistem hatası (HTTP '.$response['status'].'). Migration çalıştırıldı mı? (php artisan migrate)';
    }

    $msg = is_array($response['body']) ? ($response['body']['message'] ?? null) : null;

    return $msg ?: 'API yanıt vermedi (HTTP '.$response['status'].'). Adres: '.$url;
}

function api_token_looks_invalid(array $config): ?string
{
    $token = trim((string) ($config['api_token'] ?? ''));

    if ($token === '' || str_contains($token, 'BURAYA') || ! str_starts_with($token, 'ef_')) {
        return 'config.php içinde geçerli api_token yok. Ayarlar → Harici Sistem API\'den aldığınız ef_... anahtarını yapıştırın.';
    }

    if (strlen($token) < 20) {
        return 'api_token çok kısa görünüyor; tam anahtarı kopyaladığınızdan emin olun.';
    }

    return null;
}
