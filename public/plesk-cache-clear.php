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

foreach (['route:clear', 'config:clear', 'view:clear'] as $cmd) {
    $kernel->call($cmd);
    echo "php artisan {$cmd} ... OK\n";
}

$routesFile = $root . '/routes/web.php';
$hasEditRoute = is_file($routesFile) && str_contains(file_get_contents($routesFile), "->name('accounts.edit')");

echo "\nroutes/web.php accounts.edit: " . ($hasEditRoute ? 'VAR (kod guncel)' : 'YOK — Git Pull yapin') . "\n";

$routeCache = $root . '/bootstrap/cache/routes-v7.php';
echo 'bootstrap/cache/routes-v7.php: ' . (is_file($routeCache) ? 'HALA VAR (silin veya deploy calistirin)' : 'YOK (iyi)') . "\n";

echo "\nSimdi /emails/accounts sayfasini yenileyin.\n";
echo "Bu dosyayi silin: public/plesk-cache-clear.php\n";
