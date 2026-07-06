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

/** @param array{status: int, body: mixed, error: ?string, raw: ?string, redirect_url?: ?string} $response */
function describe_api_failure(ApiClient $api, array $response): string
{
    $url = $api->baseUrl().'/me';

    if ($response['status'] === 0) {
        return 'Ana sisteme ulaşılamadı: '.($response['error'] ?? 'bağlantı hatası')
            .'. Adres: '.$url;
    }

    if (in_array($response['status'], [301, 302, 307, 308], true)) {
        $to = $response['redirect_url'] ?? '';
        $hint = api_url_hint($api->baseUrl());

        return 'Yanlış API adresi (HTTP '.$response['status'].' yönlendirme). '
            .'api_base_url Laravel ana sisteminiz olmalı (ör. https://portal.kurtulum.com/api), '
            .'log.kurtulum.com (panel) değil.'
            .($to !== '' ? ' Yönlendirme: '.$to : '')
            .($hint !== '' ? ' '.$hint : '');
    }

    if ($response['status'] === 503) {
        return 'Harici API ana sistemde kapalı. Ticari → Ayarlar → Harici Sistem API → "Harici API etkin" işaretleyip kaydedin.';
    }

    if ($response['status'] === 401) {
        return 'API anahtarı reddedildi (401). config.php içindeki api_token, Ayarlar\'da oluşturduğunuz ef_... anahtarı ile aynı olmalı.';
    }

    if ($response['status'] === 404) {
        return 'API adresi bulunamadı (404): '.$url
            .'. XAMPP için genelde: http://localhost/ticari/public/api';
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

function api_url_looks_invalid(array $config): ?string
{
    $base = rtrim(trim((string) ($config['api_base_url'] ?? '')), '/');

    if ($base === '') {
        return 'api_base_url boş.';
    }

    return api_url_hint($base);
}

function api_error_message(array $response, string $fallback): string
{
    if (is_array($response['body'] ?? null)) {
        $msg = $response['body']['message'] ?? null;
        if (is_string($msg) && $msg !== '') {
            return $msg;
        }
    }

    return $fallback.' (HTTP '.(int) ($response['status'] ?? 0).')';
}

function fmt_money($amount, string $currency = 'TRY'): string
{
    return number_format((float) $amount, 2, ',', '.').' '.$currency;
}

function api_url_hint(string $base): string
{
    $host = parse_url($base, PHP_URL_HOST) ?: '';
    $lower = strtolower($base);

    if (str_contains($host, 'portal.') && str_contains($lower, '/api')) {
        return '';
    }

    if (str_contains($host, 'log.') && str_contains($lower, '/api')) {
        return 'log.* üzerinde Laravel yok; api_base_url Laravel domain\'ine işaret etmeli (ör. portal.kurtulum.com/api).';
    }

    if (str_contains($lower, '/ticari/public') || str_contains($lower, '/public/api')) {
        return 'Canlı sunucuda genelde https://portal.kurtulum.com/api yeterli (/ticari/public gerekmez).';
    }

    if (str_starts_with($lower, 'http://') && ! str_contains($host, 'localhost')) {
        return 'Canlı ortamda https:// kullanın.';
    }

    return '';
}

function fmt_date(?string $value, bool $withTime = false): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    try {
        $dt = new DateTimeImmutable($value);

        return $dt->format($withTime ? 'd.m.Y H:i' : 'd.m.Y');
    } catch (Throwable) {
        return $value;
    }
}

function order_status_label(?string $status): string
{
    return match ($status) {
        'draft' => 'Taslak',
        'confirmed' => 'Onaylandı',
        'production' => 'Üretimde',
        'ready' => 'Hazır',
        'shipped' => 'Sevk edildi',
        'delivered' => 'Teslim edildi',
        'cancelled' => 'İptal',
        default => $status ?: '—',
    };
}

function shipment_status_label(?string $status): string
{
    return match ($status) {
        'draft' => 'Taslak',
        'booked' => 'Rezerve',
        'in_transit' => 'Yolda',
        'at_port' => 'Limanda',
        'customs' => 'Gümrükte',
        'delivered' => 'Teslim',
        'cancelled' => 'İptal',
        default => $status ?: '—',
    };
}

function transport_label(?string $mode): string
{
    return match ($mode) {
        'road' => 'Kara',
        'sea' => 'Deniz',
        'air' => 'Hava',
        'rail' => 'Demiryolu',
        'multimodal' => 'Multimodal',
        default => $mode ?: '—',
    };
}

function cost_status_label(?string $status): string
{
    return match ($status) {
        'pending' => 'Bekliyor',
        'paid' => 'Ödendi',
        'delivered' => 'Teslim',
        default => $status ?: '—',
    };
}

function status_badge(?string $status, string $type = 'order'): string
{
    $label = $type === 'shipment' ? shipment_status_label($status) : order_status_label($status);
    $class = match ($status) {
        'confirmed', 'delivered', 'paid' => 'bg-success',
        'cancelled' => 'bg-danger',
        'draft' => 'bg-secondary',
        'in_transit', 'production', 'ready', 'shipped', 'pending' => 'bg-primary',
        default => 'bg-azure',
    };

    return '<span class="badge '.$class.'">'.$label.'</span>';
}

function whatsapp_url(?string $phone): ?string
{
    if ($phone === null || trim($phone) === '') {
        return null;
    }

    $digits = preg_replace('/\D+/', '', $phone);

    return $digits !== '' ? 'https://wa.me/'.$digits : null;
}

/** @param array<string, mixed> $meta */
function pagination_html(array $meta, array $query = []): string
{
    $current = (int) ($meta['current_page'] ?? 1);
    $last = (int) ($meta['last_page'] ?? 1);

    if ($last <= 1) {
        return '';
    }

    $build = static function (int $page) use ($query): string {
        $query['page'] = $page;
        $qs = http_build_query($query);

        return '?'.$qs;
    };

    $html = '<nav class="mt-3"><ul class="pagination pagination-sm mb-0">';
    if ($current > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="'.e($build($current - 1)).'">Önceki</a></li>';
    }
    $html .= '<li class="page-item disabled"><span class="page-link">Sayfa '.$current.' / '.$last.'</span></li>';
    if ($current < $last) {
        $html .= '<li class="page-item"><a class="page-link" href="'.e($build($current + 1)).'">Sonraki</a></li>';
    }
    $html .= '</ul></nav>';

    return $html;
}

function page_actions(string $html): string
{
    return '<div class="page-header d-print-none mb-3"><div class="row align-items-center"><div class="col"><h2 class="page-title mb-0">'.$html.'</h2></div></div></div>';
}
