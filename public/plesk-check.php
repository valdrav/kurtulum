<?php
/**
 * Gecici teşhis — kurulum sonrasi silin: public/plesk-check.php
 */
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);
$checks = [];

$checks['PHP sürümü >= 8.2'] = version_compare(PHP_VERSION, '8.2.0', '>=')
    ? 'OK (' . PHP_VERSION . ')'
    : 'HATA: ' . PHP_VERSION;

$checks['vendor/autoload.php'] = file_exists($root . '/vendor/autoload.php') ? 'OK' : 'HATA — ZIP vendor içermiyor veya yanlış klasör';

$checks['.env dosyası'] = file_exists($root . '/.env') ? 'OK' : 'HATA — .env yok (ZIP’ten çıkmamış olabilir)';

$env = file_exists($root . '/.env') ? file_get_contents($root . '/.env') : '';
$checks['APP_KEY'] = (str_contains($env, 'APP_KEY=base64:') && preg_match('/APP_KEY=base64:[A-Za-z0-9+\/=]{20,}/', $env))
    ? 'OK'
    : 'HATA — SSH: php artisan key:generate --force';

$checks['storage yazılabilir'] = is_writable($root . '/storage') ? 'OK' : 'HATA — chmod 775 storage';

$checks['bootstrap/cache yazılabilir'] = is_writable($root . '/bootstrap/cache') ? 'OK' : 'HATA — chmod 775 bootstrap/cache';

$checks['public/index.php'] = file_exists($root . '/public/index.php') ? 'OK' : 'HATA';

$checks['Document root'] = (basename(__DIR__) === 'public' && file_exists(__DIR__ . '/index.php'))
    ? 'OK (public klasöründesiniz)'
    : 'UYARI — Hosting document root public olmalı';

$checks['pdo_mysql'] = extension_loaded('pdo_mysql') ? 'OK' : 'HATA — Plesk PHP eklentilerinden açın';

$checks['mod_rewrite / .htaccess'] = file_exists(__DIR__ . '/.htaccess') ? 'OK (.htaccess mevcut)' : 'UYARI';

echo "=== ExportFlow Plesk Teşhis ===\n\n";
foreach ($checks as $label => $result) {
    echo str_pad($label, 28) . ': ' . $result . "\n";
}

echo "\n--- Önerilen URL ---\n";
echo "Kurulum: https://" . ($_SERVER['HTTP_HOST'] ?? 'portal.kurtulum.com') . "/install\n";
echo "Sağlık:  https://" . ($_SERVER['HTTP_HOST'] ?? 'portal.kurtulum.com') . "/up\n";
echo "\n500 devam ediyorsa: storage/logs/laravel.log dosyasına bakın.\n";
echo "Bu dosyayı kurulumdan sonra silin.\n";
