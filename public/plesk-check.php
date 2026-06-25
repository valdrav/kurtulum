<?php
/**
 * Gecici teşhis — kurulum sonrasi silin: public/plesk-check.php
 */
header('Content-Type: text/plain; charset=utf-8');

$publicDir = __DIR__;
$root = dirname($publicDir);
$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';

echo "=== ExportFlow Plesk Teşhis ===\n\n";

// --- Document root (en kritik) ---
$docRootNorm = rtrim(str_replace('\\', '/', $docRoot), '/');
$publicNorm = rtrim(str_replace('\\', '/', $publicDir), '/');
$docRootIsPublic = ($docRootNorm === $publicNorm)
    || str_ends_with($docRootNorm, '/public');

echo str_pad('Document root (Apache)', 32) . ': ' . ($docRoot ?: '(bilinmiyor)') . "\n";
echo str_pad('public/ yolu', 32) . ': ' . $publicDir . "\n";
echo str_pad('Proje kökü (artisan)', 32) . ': ' . $root . "\n";

if ($docRootIsPublic) {
    echo str_pad('Document root kontrolü', 32) . ": OK — public klasörü\n";
} else {
    echo str_pad('Document root kontrolü', 32) . ": HATA — public DEĞİL\n";
    echo "\n!!! 404 NEDENİ BÜyük İHTİMALLE BU !!!\n";
    echo "Plesk → Websites & Domains → portal.kurtulum.com → Hosting Settings\n";
    echo "Document root: public  (httpdocs veya site kökü OLMAYACAK)\n";
    echo "Kaydet → birkaç saniye bekleyin → /plesk-check.php tekrar açın\n\n";
}

$checks = [];

$checks['PHP sürümü >= 8.2'] = version_compare(PHP_VERSION, '8.2.0', '>=')
    ? 'OK (' . PHP_VERSION . ')'
    : 'HATA: ' . PHP_VERSION;

$checks['artisan (proje kökü)'] = file_exists($root . '/artisan') ? 'OK' : 'HATA — dosyalar yanlış klasörde veya eksik';

$checks['vendor/autoload.php'] = file_exists($root . '/vendor/autoload.php')
    ? 'OK'
    : 'HATA — SSH: composer install --no-dev';

$checks['.env dosyası'] = file_exists($root . '/.env') ? 'OK' : 'HATA — .env.plesk.example → .env kopyalayın';

$env = file_exists($root . '/.env') ? file_get_contents($root . '/.env') : '';
$checks['APP_KEY'] = (str_contains($env, 'APP_KEY=base64:') && preg_match('/APP_KEY=base64:[A-Za-z0-9+\/=]{20,}/', $env))
    ? 'OK'
    : 'HATA — php artisan key:generate --force';

$checks['APP_INSTALLED'] = str_contains($env, 'APP_INSTALLED=false')
    ? 'OK (kurulum bekleniyor)'
    : (str_contains($env, 'APP_INSTALLED=true') ? 'OK (kurulu)' : 'UYARI — .env içinde APP_INSTALLED=false olmalı');

$checks['storage yazılabilir'] = is_writable($root . '/storage') ? 'OK' : 'HATA — File Manager: storage → İzinler → 775 (alt klasörlere uygula)';

$logsDir = $root . '/storage/logs';
if (! is_dir($logsDir)) {
    @mkdir($logsDir, 0775, true);
}
$checks['storage/logs yazılabilir'] = is_writable($logsDir)
    ? 'OK'
    : 'HATA — File Manager: storage/logs → İzinler → 775';

$viewCache = $root . '/storage/framework/views';
if (! is_dir($viewCache)) {
    @mkdir($viewCache, 0775, true);
}
$checks['storage/framework/views'] = is_dir($viewCache)
    ? (is_writable($viewCache) ? 'OK' : 'HATA — chmod 775 storage/framework/views')
    : 'HATA — mkdir -p storage/framework/views';

$checks['bootstrap/cache yazılabilir'] = is_writable($root . '/bootstrap/cache') ? 'OK' : 'HATA — chmod 775 bootstrap/cache';

$checks['public/index.php'] = file_exists($publicDir . '/index.php') ? 'OK' : 'HATA';

$checks['public/.htaccess'] = file_exists($publicDir . '/.htaccess') ? 'OK' : 'HATA — mod_rewrite çalışmaz';

$checks['Kök .htaccess (olmamalı)'] = file_exists($root . '/.htaccess')
    ? 'HATA — silin: rm -f .htaccess (AH00124 döngüsü)'
    : 'OK';

$checks['pdo_mysql'] = extension_loaded('pdo_mysql') ? 'OK' : 'HATA — Plesk PHP eklentilerinden açın';

$checks['mod_rewrite'] = function_exists('apache_get_modules')
    ? (in_array('mod_rewrite', apache_get_modules(), true) ? 'OK' : 'UYARI — mod_rewrite kapalı olabilir')
    : 'Bilinmiyor (nginx proxy olabilir, sorun değil)';

echo "\n--- Dosya ve ortam ---\n";
foreach ($checks as $label => $result) {
    echo str_pad($label, 32) . ': ' . $result . "\n";
}

// Laravel bootstrap test
echo "\n--- Laravel ---\n";
if (file_exists($root . '/vendor/autoload.php')) {
    try {
        require $root . '/vendor/autoload.php';
        $app = require $root . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        $request = Illuminate\Http\Request::create('/install', 'GET');
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        echo str_pad('/install route testi', 32) . ': ' . ($status < 400 ? "OK (HTTP $status)" : "HATA (HTTP $status)") . "\n";
        $kernel->terminate($request, $response);
    } catch (Throwable $e) {
        echo str_pad('/install route testi', 32) . ': HATA — ' . $e->getMessage() . "\n";
    }
} else {
    echo str_pad('/install route testi', 32) . ": atlandı (vendor yok)\n";
}

$host = $_SERVER['HTTP_HOST'] ?? 'portal.kurtulum.com';
echo "\n--- URL'ler ---\n";
echo "Teşhis:  https://{$host}/plesk-check.php\n";
echo "Kurulum: https://{$host}/install\n";
echo "Sağlık:  https://{$host}/up\n";
echo "Giriş:   https://{$host}/login\n";

echo "\n--- Sıra ---\n";
echo "1. Document root = public\n";
echo "2. Kök .htaccess yok\n";
echo "3. composer install --no-dev\n";
echo "4. .env + key:generate + chmod 775 storage bootstrap/cache\n";
echo "5. /install\n";
echo "\nBu dosyayı kurulumdan sonra silin.\n";
