#!/bin/bash
# Plesk Git -> Additional deployment actions (Pull SONRASI calisir)
set -e
cd "$(dirname "$0")/.."

PHP="${PHP_BIN:-php}"

if [ -z "${COMPOSER_BIN:-}" ]; then
    if command -v composer >/dev/null 2>&1; then
        COMPOSER="composer"
    elif [ -f /usr/local/bin/composer ]; then
        COMPOSER="$PHP /usr/local/bin/composer"
    elif [ -f /opt/psa/var/modules/composer/composer.phar ]; then
        COMPOSER="$PHP /opt/psa/var/modules/composer/composer.phar"
    else
        COMPOSER="composer"
    fi
else
    COMPOSER="$COMPOSER_BIN"
fi

echo "=== Kurtulum ERP deploy ==="
echo "Composer: $COMPOSER"

INSTALLED=false
if [ -f .env ] && grep -q 'APP_INSTALLED=true' .env 2>/dev/null; then
    INSTALLED=true
    echo "Mod: GUNCELLEME (veritabani ve .env korunur)"
else
    echo "Mod: ILK KURULUM (.env yoksa olusturulur, /install acik)"
fi

rm -f .htaccess

if [ ! -f artisan ]; then
    echo "HATA: artisan bulunamadi."
    exit 1
fi

$COMPOSER install --no-dev --optimize-autoloader --no-interaction

if [ ! -f vendor/spatie/laravel-permission/src/Models/Permission.php ]; then
    echo "HATA: spatie/laravel-permission yuklenmedi. composer install basarisiz."
    exit 1
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
