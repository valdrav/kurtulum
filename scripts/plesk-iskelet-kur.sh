#!/bin/bash
# Plesk SSH — Laravel iskeleti + ExportFlow kaynak dosyalari
# Kullanim: bash scripts/plesk-iskelet-kur.sh
set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

PHP="${PHP_BIN:-php}"
COMPOSER="${COMPOSER_BIN:-composer}"

echo "=== ExportFlow — Plesk kurulum ==="
echo "Klasor: $ROOT"
echo ""

copy_kaynak() {
    local SRC="$1"
    echo "    Kaynak: $SRC"
    cp -a "$SRC/app" "$ROOT/"
    cp -a "$SRC/bootstrap/app.php" "$SRC/bootstrap/providers.php" "$ROOT/bootstrap/"
    cp -a "$SRC/config/"* "$ROOT/config/"
    cp -a "$SRC/database" "$ROOT/"
    cp -a "$SRC/lang" "$ROOT/"
    [ -d "$SRC/modules" ] && cp -a "$SRC/modules" "$ROOT/"
    cp -a "$SRC/resources" "$ROOT/"
    cp -a "$SRC/routes/"* "$ROOT/routes/"
    mkdir -p "$ROOT/public/css"
    [ -d "$SRC/public/css" ] && cp -a "$SRC/public/css/"* "$ROOT/public/css/" 2>/dev/null || true
    [ -f "$SRC/public/sw.js" ] && cp "$SRC/public/sw.js" "$ROOT/public/"
    [ -f "$SRC/public/plesk-check.php" ] && cp "$SRC/public/plesk-check.php" "$ROOT/public/"
    cp "$SRC/composer.json" "$SRC/composer.lock" "$ROOT/"
    [ -f "$SRC/artisan" ] && cp "$SRC/artisan" "$ROOT/"
    [ -f "$SRC/.env.plesk" ] && cp "$SRC/.env.plesk" "$ROOT/.env"
}

# --- 1) Bos klasorde Laravel iskeleti ---
if [ ! -f "$ROOT/artisan" ] && [ ! -f "$ROOT/composer.json" ]; then
    echo "[1/6] Bos klasor — Laravel 12 iskeleti indiriliyor..."
    TMP="_laravel_tmp_$$"
    $COMPOSER create-project laravel/laravel "$TMP" "12.*" --no-interaction --prefer-dist --no-dev
    shopt -s dotglob 2>/dev/null || true
    mv "$TMP"/* "$ROOT/"
    rm -rf "$TMP"
    echo "    Iskelet olusturuldu."
elif [ ! -f "$ROOT/artisan" ]; then
    echo "HATA: composer.json var ama artisan yok. ZIP tam acilmamis olabilir."
    exit 1
else
    echo "[1/6] Proje dosyalari mevcut (artisan OK)."
fi

# --- 2) kaynak/ alt klasorunden kopyala (opsiyonel) ---
if [ -d "$ROOT/kaynak/app" ]; then
    echo "[2/6] kaynak/ klasorunden dosyalar kopyalaniyor..."
    copy_kaynak "$ROOT/kaynak"
elif [ -f "$ROOT/config/ticari.php" ]; then
    echo "[2/6] ExportFlow kaynak dosyalari zaten yerinde."
else
    echo "HATA: config/ticari.php bulunamadi. exportflow-kaynak.zip dogru acildi mi?"
    exit 1
fi

# --- 3) .env ---
echo "[3/6] .env ayarlari..."
if [ ! -f "$ROOT/.env" ]; then
    [ -f "$ROOT/.env.plesk" ] && cp "$ROOT/.env.plesk" "$ROOT/.env" || cp "$ROOT/.env.example" "$ROOT/.env"
fi
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' "$ROOT/.env" 2>/dev/null || echo 'SESSION_DRIVER=file' >> "$ROOT/.env"
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' "$ROOT/.env" 2>/dev/null || echo 'CACHE_STORE=file' >> "$ROOT/.env"
sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' "$ROOT/.env" 2>/dev/null || echo 'QUEUE_CONNECTION=sync' >> "$ROOT/.env"
sed -i 's/^APP_INSTALLED=.*/APP_INSTALLED=false/' "$ROOT/.env" 2>/dev/null || echo 'APP_INSTALLED=false' >> "$ROOT/.env"

# --- 4) Composer (vendor yoksa veya eksikse) ---
echo "[4/6] composer install..."
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

echo "    Spatie publish..."
$PHP artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force 2>/dev/null || true
$PHP artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --force 2>/dev/null || true

# --- 5) APP_KEY + izinler ---
echo "[5/6] APP_KEY ve izinler..."
if ! grep -q 'APP_KEY=base64:' "$ROOT/.env" 2>/dev/null; then
    $PHP artisan key:generate --force --no-interaction
fi
chmod -R 775 "$ROOT/storage" "$ROOT/bootstrap/cache" 2>/dev/null || true
$PHP artisan storage:link --force 2>/dev/null || true
$PHP artisan config:clear 2>/dev/null || true

echo "[6/6] Tamam."
echo ""
echo "  Document root -> public"
echo "  https://portal.kurtulum.com/install"
echo "  Teşhis: /plesk-check.php"
echo ""
