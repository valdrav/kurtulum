# GitHub + Plesk

**Akış:** bilgisayardan `git push` → Plesk'te **Pull** → `bash scripts/plesk-deploy.sh`

`vendor` GitHub'a gitmez; sunucuda `composer install` çalışır.

---

## Bilgisayar — ilk gönderim

```powershell
cd c:\xampp\htdocs\ticari
git init
git add .
git commit -m "Ilk surum"
git branch -M main
git remote add origin https://github.com/valdrav/kurtulum.git
git push -u origin main
```

`.env` commit edilmez. GitHub şifre yerine **Personal Access Token** kullanın.

## Bilgisayar — güncelleme

```powershell
git add .
git commit -m "Ne degisti"
git push
```

---

## Plesk — ilk kurulum (bir kez)

1. **Extensions** → **Git** → Install
2. `portal.kurtulum.com` → **Git** → **Clone**
   - URL: `https://github.com/valdrav/kurtulum.git`
   - Hedef: site kökü (`artisan` burada olacak)
3. **Hosting Settings** → document root: **`public`**
4. **Databases** → MariaDB veritabanı oluşturun (boş, tablo gerekmez)
5. **File Manager** → `.env.plesk.example` dosyasını `.env` olarak kopyalayın, MariaDB bilgilerini doldurun:

```env
APP_URL=https://portal.kurtulum.com
APP_INSTALLED=false
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=...    # Plesk Databases
DB_USERNAME=...
DB_PASSWORD=...
```

> Plesk **MariaDB** kullanır. `.env` içinde `DB_CONNECTION=mysql` doğrudur (Laravel MariaDB’yi bu sürücüyle bağlar).

6. Plesk **Git** → **Pull** / Deploy
7. **https://portal.kurtulum.com/install**

---

## Plesk — her push sonrası

**Git** → **Pull**, ardından:

```bash
bash scripts/plesk-deploy.sh
```

Otomatik deploy için Plesk Git → **Additional deploy actions**:

```bash
bash scripts/plesk-deploy.sh
```

---

## GitHub'a gitmez

| Dosya | Neden |
|-------|--------|
| `.env` / `.env.plesk` | Şifreler — GitHub'a gitmez |
| `.env.plesk.example` | Şablon (şifresiz) — GitHub'a gider |
| `vendor/` | Sunucuda composer |
| `storage/logs/` | Log |
| `node_modules/` | Gerek yok |

---

## Sık hatalar

| Hata | Çözüm |
|------|--------|
| 404 (Plesk sayfası) | Document root = `public`, `/ping.php` test |
| AH00124 redirect | Kök `.htaccess` sil |
| valid cache path | Plesk Pull sonrası deploy script çalışsın |
| **403 ModSecurity** | Plesk → site → Web Application Firewall → Kapalı |
| Log Permission denied | File Manager → `storage` ve `bootstrap/cache` → izinler 775 |
| sqlite / database.sqlite | `.env` → `DB_CONNECTION=mysql` (MariaDB), Plesk Databases bilgileri |
| vendor hatası | `rm -rf vendor && composer install --no-dev` |
| `/install` 500 | `.env` → `SESSION_DRIVER=file`, izinler 775 |

Detaylı sorun giderme: [DEPLOY-PLESK.md](DEPLOY-PLESK.md)
