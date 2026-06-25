#!/bin/bash
# Plesk Git -> "Additional deployment actions" veya SSH ile calistirin
set -e
cd "$(dirname "$0")/.."

PHP="${PHP_BIN:-php}"
COMPOSER="${COMPOSER_BIN:-composer}"

echo "=== Kurtulum ERP deploy ==="

# Document root = public iken kok .htaccess public/ yonlendirmesi AH00124 dongusune yol acar
rm -f .htaccess

if [ ! -f artisan ]; then
    echo "HATA: artisan bulunamadi. Git kok klasore mi clone edildi kontrol edin."
    echo "Olmasi gereken: .../portal.kurtulum.com/artisan"
    exit 1
fi

$COMPOSER install --no-dev --optimize-autoloader --no-interaction

if [ ! -f .env ]; then
    cp .env.plesk .env 2>/dev/null || cp .env.example .env
fi

if ! grep -q 'APP_KEY=base64:' .env 2>/dev/null; then
    $PHP artisan key:generate --force --no-interaction
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
$PHP artisan storage:link --force 2>/dev/null || true
$PHP artisan config:clear
$PHP artisan view:clear

if [ -f scripts/plesk-verify.sh ]; then
    bash scripts/plesk-verify.sh || true
fi

if grep -q 'APP_INSTALLED=true' .env 2>/dev/null; then
    $PHP artisan migrate --force --no-interaction 2>/dev/null || true
    $PHP artisan config:cache --no-interaction 2>/dev/null || true
    $PHP artisan route:cache --no-interaction 2>/dev/null || true
    $PHP artisan view:cache --no-interaction 2>/dev/null || true
fi

echo "Deploy tamam."
