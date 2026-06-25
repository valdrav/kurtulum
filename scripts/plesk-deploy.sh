#!/bin/bash
# Plesk Git -> Pull sonrasi (composer sunucuda calismasa da vendor repodan gelir)
set -e
cd "$(dirname "$0")/.."

PHP="${PHP_BIN:-php}"

INSTALLED=false
if [ -f .env ] && grep -q 'APP_INSTALLED=true' .env 2>/dev/null; then
    INSTALLED=true
    echo "Mod: GUNCELLEME"
else
    echo "Mod: ILK KURULUM"
fi

echo "=== Kurtulum ERP deploy ==="
rm -f .htaccess

if [ ! -f artisan ]; then
    echo "HATA: artisan bulunamadi."
    exit 1
fi

if [ ! -f vendor/spatie/laravel-permission/src/Models/Permission.php ]; then
    echo "HATA: vendor/ repoda yok veya pull eksik. Bilgisayardan push + Plesk Pull yapin."
    exit 1
fi

echo "vendor/spatie OK (Git ile geldi)"

# Sunucuda composer varsa paketleri senkronize et (yoksa atla)
if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || true
elif [ -f /opt/psa/var/modules/composer/composer.phar ]; then
    $PHP /opt/psa/var/modules/composer/composer.phar install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || true
fi

if [ ! -f .env ]; then
    cp .env.plesk.example .env 2>/dev/null || cp .env.example .env
fi

if [ "$INSTALLED" = false ] && ! grep -q 'APP_KEY=base64:' .env 2>/dev/null; then
    $PHP artisan key:generate --force --no-interaction
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R u+rwX,g+rwX storage 2>/dev/null || true

$PHP artisan storage:link --force 2>/dev/null || true

if [ "$INSTALLED" = true ]; then
    $PHP artisan migrate --force --no-interaction 2>/dev/null || true
    $PHP artisan optimize --no-interaction 2>/dev/null || true
else
    $PHP artisan optimize:clear --no-interaction 2>/dev/null || true
fi

echo "Deploy tamam."
