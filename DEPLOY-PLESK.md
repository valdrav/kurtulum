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
cp .env.plesk.example .env    # MariaDB bilgilerini doldurun
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

### Log / storage izin hatası (Permission denied)

`storage/logs/laravel.log` yazılamıyor.

**Plesk File Manager:**

1. `storage` klasörü → sağ tık → **Change Permissions** → **775** → alt klasörlere uygula
2. Aynı işlemi `bootstrap/cache` için yapın
3. `storage/logs` içinde `laravel.log` varsa ve hata devam ediyorsa silin (yeniden oluşur)

Kod tarafında log yazılamazsa Apache loguna düşer; kurulum yine açılabilir.

### .env veritabanı (sqlite hatası)

Kurulum sayfasında `database.sqlite does not exist` görürseniz sunucudaki `.env` yanlış şablondan kalmış demektir.

Plesk **File Manager** → `.env` dosyasını açın:

```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=file
CACHE_STORE=file
APP_INSTALLED=false
```

**Plesk MariaDB:** Veritabanı bilgilerini **Databases** ekranından alın. Laravel’de sürücü adı `mysql` kalır — MariaDB ile uyumludur, `mariadb` yazmayın.

Referans: `.env.plesk.example` (şifre GitHub'a gitmez — sunucuda `.env` içine yazın)

### 403 — ModSecurity (Comodo WAF)

Log: `ModSecurity: Access denied` / `PHP source code leakage` / kural **214620**

Sunucu güvenlik duvarı Laravel sayfasını yanlışlıkla engelliyor. **Uygulama hatası değil.**

**Plesk panelden:**

1. **Websites & Domains** → `portal.kurtulum.com`
2. **Web Application Firewall** (veya **ModSecurity** / **Güvenlik duvarı**)
3. Bu site için **Kapalı** veya **Yalnızca izleme (Detection only)** seçin
4. Kaydet

Geçici olarak doğrudan kurulum adresini deneyin: `https://portal.kurtulum.com/install`

`.env` içinde `APP_DEBUG=false` olmalı (hata sayfaları WAF’i tetikler).

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
