<?php

/**
 * Bağlantı testi — giriş yapmadan API durumunu kontrol eder.
 * Kurulumdan sonra bir kez açın, sonra silin veya erişimi kapatın.
 */
require __DIR__.'/includes/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

$checks = [];

$checks[] = ['label' => 'config.php', 'ok' => is_file(__DIR__.'/includes/config.php')];

$tokenErr = api_token_looks_invalid($config);
$checks[] = [
    'label' => 'API anahtarı (config.php → api_token)',
    'ok' => $tokenErr === null,
    'detail' => $tokenErr ?: 'ef_... formatında tanımlı',
];

$url = rtrim($config['api_base_url'] ?? '', '/').'/me';
$checks[] = ['label' => 'API adresi', 'ok' => $url !== '/me', 'detail' => $url];

$urlWarn = api_url_looks_invalid($config);
if ($urlWarn !== null) {
    $checks[] = ['label' => 'Adres uyarısı', 'ok' => false, 'detail' => $urlWarn];
}

$response = $api->get('/me');
$checks[] = [
    'label' => 'HTTP yanıtı',
    'ok' => $response['status'] === 200,
    'detail' => 'HTTP '.$response['status'].($response['error'] ? ' — '.$response['error'] : ''),
];

if ($response['status'] !== 200) {
    $checks[] = [
        'label' => 'Teşhis',
        'ok' => false,
        'detail' => describe_api_failure($api, $response),
    ];
} else {
    $body = $response['body'];
    $checks[] = [
        'label' => 'Bağlantı adı',
        'ok' => true,
        'detail' => $body['connection'] ?? '—',
    ];
    $checks[] = [
        'label' => 'Müşteri',
        'ok' => true,
        'detail' => $body['customer']['company_name'] ?? '—',
    ];
}

$allOk = array_reduce($checks, fn ($c, $i) => $c && ($i['ok'] || ($i['label'] === 'Teşhis')), true)
    && ($response['status'] === 200);

?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>API Bağlantı Testi</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="wrap">
    <main class="main">
        <h1>API bağlantı testi</h1>
        <p class="meta">Subdomain paneli ↔ ana sistem API kontrolü. Migration burada gerekmez.</p>

        <?php if ($allOk): ?>
        <div class="alert ok">Bağlantı başarılı. <a href="login.php">Giriş sayfasına git</a></div>
        <?php else: ?>
        <div class="alert err">Bağlantı henüz tamam değil. Aşağıdaki maddeleri düzeltin.</div>
        <?php endif; ?>

        <table>
            <thead><tr><th>Kontrol</th><th>Durum</th><th>Detay</th></tr></thead>
            <tbody>
            <?php foreach ($checks as $row): ?>
                <tr>
                    <td><?= e($row['label']) ?></td>
                    <td><?= $row['ok'] ? '✓ OK' : '✗ Hata' ?></td>
                    <td class="meta"><?= e($row['detail'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Ana sistemde yapılacaklar (subdomain değil)</h2>
        <ol class="meta">
            <li><strong>Ayarlar → Harici Sistem API</strong> → API etkin + müşteri seç + bağlantı oluştur</li>
            <li>Oluşan <code>ef_...</code> anahtarını subdomain <code>includes/config.php</code> içine yapıştır</li>
            <li>Ana sistemde bir kez: <code>php artisan migrate</code> (API tabloları için)</li>
        </ol>

        <p class="meta">Subdomain tarafında sadece PHP dosyaları + config.php yeterli.</p>
    </main>
</div>
</body>
</html>
