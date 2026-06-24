#!/bin/bash
# Plesk SSH / Terminal — kurulum sonrasi (opsiyonel, web sihirbazi yerine)
set -e
cd "$(dirname "$0")/.."

PHP="${PHP_BIN:-php}"

echo "==> Izinler..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "==> APP_KEY..."
if ! grep -q 'APP_KEY=base64:' .env 2>/dev/null; then
    $PHP artisan key:generate --force
fi

echo "==> Veritabani..."
$PHP artisan migrate --force
$PHP artisan db:seed --class=RolesAndPermissionsSeeder --force
$PHP artisan db:seed --class=ReferenceDataSeeder --force
$PHP artisan db:seed --class=ExtensibilitySeeder --force

echo "==> Depolama..."
$PHP artisan storage:link --force 2>/dev/null || true

echo "==> Onbellek..."
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

echo ""
echo "Kurulum tamam. .env icinde APP_INSTALLED=true yapin ve giris yapin."
echo "Admin kullanicisi yoksa: php artisan tinker ile olusturun veya /install kullanin."
