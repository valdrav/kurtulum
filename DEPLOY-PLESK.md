# Plesk Deploy — portal.kurtulum.com

Kurulum ve güncelleme **Git ile** yapılır. Ana rehber: **[GITHUB-KURULUM.md](GITHUB-KURULUM.md)**

## Akış

```
[Bilgisayar]  git add → commit → push
[Plesk]       Git Pull  →  bash scripts/plesk-deploy.sh  →  /install veya /login
```

## Plesk (bir kez)

1. Git → clone: `https://github.com/valdrav/kurtulum.git` (site kökü)
2. **Hosting Settings** → document root: **`public`**
3. SSH veya Plesk Terminal:

```bash
cd /var/www/vhosts/kurtulum.com/portal.kurtulum.com
cp .env.plesk .env    # DB bilgilerini doldurun
bash scripts/plesk-deploy.sh
```

4. Tarayıcı: **https://portal.kurtulum.com/install**

## Her güncelleme

Bilgisayar:

```powershell
git add .
git commit -m "Aciklama"
git push
```

Plesk → **Git** → **Pull** (veya otomatik deploy script):

```bash
bash scripts/plesk-deploy.sh
```

Plesk Git → **Additional deploy actions** alanına doğrudan `bash scripts/plesk-deploy.sh` yazabilirsiniz.

---

## Sorun giderme

### Document root

Document root **`public`** olmalı. Kök `.htaccess` **olmamalı** (`public/.htaccess` kalır).

Test: `https://portal.kurtulum.com/ping.php` → `OK` yazmalı.

### AH00124 — 10 internal redirects (500)

Kök `.htaccess` silin: `rm -f .htaccess`  
Document root: `public`

### 404 — Plesk "Page Not Found"

Laravel'e ulaşmıyor → document root `public` değil.

### InvalidArgumentException — valid cache path

```bash
mkdir -p storage/framework/{views,sessions,cache/data} storage/logs
chmod -R 775 storage bootstrap/cache
php artisan config:clear
```

### vendor / karışık sürüm

```bash
rm -rf vendor
composer install --no-dev --optimize-autoloader
```

### Teşhis

`https://portal.kurtulum.com/plesk-check.php` — kurulumdan sonra silin.

Log: `storage/logs/laravel.log`
