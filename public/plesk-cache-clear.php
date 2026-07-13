<?php
/**
 * Gecici — route/config cache temizler. Calistirdiktan sonra silin: public/plesk-cache-clear.php
 */
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);

if (! is_file($root . '/vendor/autoload.php')) {
    echo "HATA: vendor/ yok.\n";
    exit(1);
}

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "=== Kurtulum cache temizleme ===\n\n";

foreach (['route:clear', 'config:clear', 'view:clear', 'optimize:clear'] as $cmd) {
    $kernel->call($cmd);
    echo "php artisan {$cmd} ... OK\n";
}

$browserPartial = $root . '/resources/views/documents/partials/browser.blade.php';
$hasUploadBanner = is_file($browserPartial) && str_contains(file_get_contents($browserPartial), 'files-create-banner');
echo "\ndocuments browser (yukleme banner): " . ($hasUploadBanner ? 'VAR (kod guncel)' : 'YOK — Git Pull yapin') . "\n";

$iconPartial = $root . '/resources/views/documents/partials/icon.blade.php';
$hasSvgIcons = is_file($iconPartial) && str_contains(file_get_contents($iconPartial), 'files-svg-icon');
echo 'documents SVG ikonlar: ' . ($hasSvgIcons ? 'VAR' : 'YOK — Git Pull yapin') . "\n";

$tablerWoff2 = $root . '/public/vendor/tabler-icons/fonts/tabler-icons.woff2';
echo 'tabler-icons font: ' . (is_file($tablerWoff2) ? 'VAR' : 'YOK — public/vendor/tabler-icons yukleyin') . "\n";

try {
    $kernel->call('documents:sync-permissions');
    echo "php artisan documents:sync-permissions ... OK\n";
} catch (Throwable $e) {
    echo "documents:sync-permissions atlandi (komut yoksa deploy sonrasi calistirin)\n";
}

$routesFile = $root . '/routes/web.php';
$hasEditRoute = is_file($routesFile) && str_contains(file_get_contents($routesFile), "->name('accounts.edit')");

echo "\nroutes/web.php accounts.edit: " . ($hasEditRoute ? 'VAR (kod guncel)' : 'YOK — Git Pull yapin') . "\n";

$routeCache = $root . '/bootstrap/cache/routes-v7.php';
echo 'bootstrap/cache/routes-v7.php: ' . (is_file($routeCache) ? 'HALA VAR (silin veya deploy calistirin)' : 'YOK (iyi)') . "\n";

echo "\nSimdi /emails/accounts sayfasini yenileyin.\n";
echo "Bu dosyayi silin: public/plesk-cache-clear.php\n";
