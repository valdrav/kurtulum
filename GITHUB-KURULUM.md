# GitHub + Plesk Kurulum Rehberi

Projeyi GitHub’a yükleyip Plesk’te **Git ile çekerek** kurmak en temiz yoldur.  
`vendor` GitHub’a gitmez; sunucuda `composer install` çalışır (karışık sürüm sorunu olmaz).

---

## BÖLÜM 1 — Bilgisayarınızda Git kurulumu

1. İndirin: https://git-scm.com/download/win  
2. Kurulumda varsayılanları kabul edin (“Git from the command line” seçili olsun).  
3. **Cursor / PowerShell’i kapatıp yeniden açın.**

Kontrol:

```powershell
cd c:\xampp\htdocs\ticari
git --version
```

---

## BÖLÜM 2 — GitHub’da boş depo oluşturma

1. https://github.com → giriş yapın  
2. Sağ üst **+** → **New repository**  
3. Ayarlar:
   - **Repository name:** `exportflow` (veya `ticari`)
   - **Private** (önerilir — ticari yazılım)
   - **README, .gitignore, license EKLEMEYİN** (boş repo)
4. **Create repository**

Sayfada şuna benzer bir adres görürsünüz:

```
https://github.com/valdrav/kurtulum.git
```

Bunu not alın.

---

## BÖLÜM 3 — Projeyi ilk kez GitHub’a gönderme

PowerShell veya Cursor terminal:

```powershell
cd c:\xampp\htdocs\ticari

git init
git add .
git status
```

`git status` çıktısında **`.env` görünmemeli** (gizli kalır).  
Görünürse commit etmeyin.

```powershell
git commit -m "Ilk surum: ExportFlow ERP"

git branch -M main

git remote add origin https://github.com/valdrav/kurtulum.git

git push -u origin main
```

GitHub kullanıcı adı + **Personal Access Token** (şifre yerine) isteyebilir:

- GitHub → **Settings** → **Developer settings** → **Personal access tokens** → **Tokens (classic)**  
- **Generate new token** → `repo` yetkisi → token’ı kopyalayın  
- `git push` şifre sorunca **token’ı yapıştırın**

---

## BÖLÜM 4 — Sonraki güncellemeler (her değişiklikten sonra)

```powershell
cd c:\xampp\htdocs\ticari
git add .
git commit -m "Ne degisti kisa aciklama"
git push
```

---

## BÖLÜM 5 — Plesk’te Git’ten çekme

### 5.1 Git eklentisini açın

Plesk → **Extensions** → **Git** → Install (yoksa).

### 5.2 Depoyu bağlayın

1. **Websites & Domains** → `portal.kurtulum.com`  
2. **Git** (veya **Git Repository**)  
3. **Clone Repository**  
   - URL: `https://github.com/valdrav/kurtulum.git`  
   - Private repo ise: GitHub kullanıcı + **Personal Access Token**  
   - Hedef klasör: site kökü (ör. `portal.kurtulum.com` veya `httpdocs`)

### 5.3 Document root

**Hosting Settings** → Document root: **`public`**

### 5.4 Sunucuda kurulum (SSH / Terminal — bir kez)

```bash
cd /var/www/vhosts/kurtulum.com/portal.kurtulum.com

composer install --no-dev --optimize-autoloader --no-interaction

cp .env.plesk .env
# veya: nano .env  (DB bilgilerini doldurun)

php artisan key:generate --force
chmod -R 775 storage bootstrap/cache
php artisan config:clear
php artisan view:clear
```

`.env` minimum (kurulum öncesi):

```env
APP_URL=https://portal.kurtulum.com
APP_INSTALLED=false
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

Tarayıcı: **https://portal.kurtulum.com/install**

### 5.5 Güncelleme (her `git push` sonrası)

Plesk Git ekranında **Pull** veya **Deploy**, ardından SSH:

```bash
cd /var/www/vhosts/kurtulum.com/portal.kurtulum.com
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## BÖLÜM 6 — Plesk otomatik deploy (isteğe bağlı)

Plesk Git → **Deploy** ayarları:

- **Deploy to:** site kökü  
- **Additional deploy actions** (SSH script):

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan view:cache
```

---

## GitHub’a GİTMEZ (bilin)

| Dosya | Neden |
|-------|--------|
| `.env` | Şifreler |
| `vendor/` | Sunucuda `composer install` |
| `node_modules/` | Gerek yok |
| `database/database.sqlite` | Local veri |
| `storage/logs/` | Log dosyaları |
| `*.zip` | Paket dosyaları |

GitHub’a **GİDER:** `app/`, `config/`, `database/migrations`, `resources/`, `routes/`, `composer.json`, `composer.lock`, `.env.plesk` (şablon)

---

## Sık hatalar

| Hata | Çözüm |
|------|--------|
| `git: command not found` | Git for Windows kur, terminali yeniden aç |
| `push` reddedildi | Token veya repo yetkisi |
| Plesk 500 / previousExceptions | `rm -rf vendor && composer install` |
| `/install` açılmıyor | `APP_KEY`, `SESSION_DRIVER=file`, izinler 775 |

---

## Kısa özet

```
[Bilgisayar]  git init → add → commit → push  →  GitHub
[Plesk]       Git clone/pull  →  composer install  →  .env  →  /install
```

Sorun olursa hangi adımda kaldığınızı yazın (ör. `git push` veya Plesk clone).
