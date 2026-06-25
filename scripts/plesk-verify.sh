#!/bin/bash
# SSH: bash scripts/plesk-verify.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ok()   { echo -e "${GREEN}OK${NC}   $1"; }
fail() { echo -e "${RED}HATA${NC} $1"; ERR=1; }
warn() { echo -e "${YELLOW}UYARI${NC} $1"; }

ERR=0
echo "=== Kurtulum ERP — sunucu doğrulama ==="
echo "Kök: $ROOT"
echo

# Document root ipucu
if [ -f "$ROOT/public/plesk-check.php" ]; then
    echo "Tarayıcı testi: https://portal.kurtulum.com/plesk-check.php"
    echo "(404 ise document root public değildir)"
    echo
fi

[ -f "$ROOT/artisan" ] && ok "artisan mevcut" || fail "artisan yok — Git yanlış klasöre mi clone edildi?"

if [ -f "$ROOT/.htaccess" ]; then
    fail "Kök .htaccess var — document root public iken silin: rm -f .htaccess"
else
    ok "Kök .htaccess yok"
fi

[ -f "$ROOT/public/index.php" ] && ok "public/index.php" || fail "public/index.php yok"
[ -f "$ROOT/public/.htaccess" ] && ok "public/.htaccess" || fail "public/.htaccess yok"

if [ -f "$ROOT/vendor/autoload.php" ]; then
    ok "vendor/autoload.php"
else
    fail "vendor yok — composer install --no-dev --optimize-autoloader"
fi

if [ -f "$ROOT/.env" ]; then
    ok ".env mevcut"
    grep -q 'APP_INSTALLED=false' "$ROOT/.env" 2>/dev/null && ok "APP_INSTALLED=false" || warn "APP_INSTALLED=true veya tanımsız"
    grep -q 'APP_KEY=base64:' "$ROOT/.env" 2>/dev/null && ok "APP_KEY dolu" || fail "APP_KEY boş — php artisan key:generate --force"
else
    fail ".env yok — .env.plesk.example dosyasını .env olarak kopyalayın"
fi

[ -w "$ROOT/storage" ] && ok "storage yazılabilir" || fail "storage yazılamıyor — chmod -R 775 storage"

for dir in storage/framework/views storage/framework/sessions storage/framework/cache/data storage/logs; do
    mkdir -p "$ROOT/$dir"
    [ -d "$ROOT/$dir" ] && ok "$dir mevcut" || fail "$dir oluşturulamadı"
done

[ -w "$ROOT/bootstrap/cache" ] && ok "bootstrap/cache yazılabilir" || fail "bootstrap/cache — chmod -R 775 bootstrap/cache"

PHP_BIN="${PHP_BIN:-php}"
PHP_VER=$($PHP_BIN -r 'echo PHP_VERSION;' 2>/dev/null || echo "0")
if $PHP_BIN -r 'exit(version_compare(PHP_VERSION,"8.2.0",">=")?0:1);' 2>/dev/null; then
    ok "PHP $PHP_VER"
else
    fail "PHP 8.2+ gerekli (şu an: $PHP_VER)"
fi

$PHP_BIN -m 2>/dev/null | grep -qi pdo_mysql && ok "pdo_mysql" || fail "pdo_mysql eklentisi kapalı"

if [ -f "$ROOT/vendor/autoload.php" ]; then
    OUT=$($PHP_BIN artisan route:list --path=install 2>&1 | grep -c install.welcome || true)
    if [ "$OUT" -ge 1 ]; then
        ok "install route kayıtlı"
    else
        fail "install route bulunamadı — php artisan route:list --path=install"
    fi
fi

echo
if [ "$ERR" -eq 0 ]; then
    echo -e "${GREEN}Tüm kontroller geçti.${NC}"
    echo "Plesk → Hosting Settings → Document root: public"
    echo "Sonra: https://portal.kurtulum.com/install"
else
    echo -e "${RED}Düzeltilmesi gereken maddeler var.${NC}"
    echo "404 alıyorsanız: Document root mutlaka public olmalı (site kökü değil)."
    exit 1
fi
