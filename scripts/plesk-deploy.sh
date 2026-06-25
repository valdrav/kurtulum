#!/bin/bash
# Plesk Git -> Additional deployment actions (Pull SONRASI calisir)
set -e
cd "$(dirname "$0")/.."

PHP="${PHP_BIN:-php}"
COMPOSER="${COMPOSER_BIN:-composer}"

echo "=== Kurtulum ERP deploy ==="

rm -f .htaccess

if [ ! -f artisan ]; then
    echo "HATA: artisan bulunamadi."
    exit 1
fi

$COMPOSER install --no-dev --optimize-autoloader --no-interaction --classmap-authoritative

if [ ! -f .env ]; then
    cp .env.plesk.example .env 2>/dev/null || cp .env.example .env
fi

if ! grep -q 'APP_KEY=base64:' .env 2>/dev/null; then
    $PHP artisan key:generate --force --no-interaction
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R u+rwX,g+rwX storage 2>/dev/null || true

$PHP artisan storage:link --force 2>/dev/null || true

if grep -q 'APP_INSTALLED=true' .env 2>/dev/null; then
    $PHP artisan migrate --force --no-interaction 2>/dev/null || true
    $PHP artisan optimize --no-interaction 2>/dev/null || true
else
    $PHP artisan optimize:clear --no-interaction 2>/dev/null || true
fi

echo "Deploy tamam."
