# Plesk Kurulum — Laravel İskelet + Dosya Aktarımı

**portal.kurtulum.com** için en anlaşılır yol: önce sunucuda Laravel iskeleti, sonra proje dosyalarını üzerine kopyalamak.

Veritabanı import **gerekmez**. `/install` sihirbazı migrate’i otomatik yapar.

---

## A) Bilgisayarınızda (1 dakika)

Proje klasöründe çift tıklayın:

```
plesk-kaynak-paket.bat
```

Oluşur: **`exportflow-kaynak.zip`** (~5–10 MB, vendor yok)

Bu ZIP içinde: `app`, `config`, `database`, `resources`, `routes`, `modules`, `composer.json` vb.

---

## B) Plesk’te alt alan adı

1. **Websites & Domains** → Subdomain: `portal.kurtulum.com`
2. **Hosting Settings** → Document root: şimdilik site kökü (script sonrası **`public`** yapacağız)
3. **PHP 8.2** veya **8.3** seçin
4. **Databases** → boş MySQL oluşturun (tablo gerekmez)

---

## C) Dosyaları yükleyin

1. **Files** → `portal.kurtulum.com` klasörü
2. **`exportflow-kaynak.zip`** yükleyin → **Extract** (site köküne açılsın)
3. Klasör yapısı şöyle olmalı:

```
portal.kurtulum.com/
  app/
  config/
  composer.json
  public/
  scripts/plesk-iskelet-kur.sh
  ...
```

---

## D) SSH / Plesk Terminal (tek komut)

**Websites & Domains** → **SSH Access** veya **Terminal** açın:

```bash
cd /var/www/vhosts/kurtulum.com/portal.kurtulum.com
```

*(Yol Plesk’te farklı olabilir — File Manager’da tam yolu kontrol edin.)*

Sonra:

```bash
bash scripts/plesk-iskelet-kur.sh
```

Script şunları yapar:

1. Laravel 12 iskeletini indirir (`composer create-project`)
2. Sizin `app/`, `config/`, `database/` … dosyalarınızı üzerine yazar
3. `composer install` (Spatie paketleri dahil)
4. `APP_KEY` üretir
5. İzinleri ayarlar

---

## E) Document root + SSL

1. **Hosting Settings** → Document root: **`public`**
2. **SSL/TLS** → Let's Encrypt → HTTPS açın

---

## F) Kurulum sihirbazı

Tarayıcıda:

```
https://portal.kurtulum.com/install
```

- MySQL bilgilerini girin (Plesk’te oluşturduğunuz DB)
- Admin hesabı oluşturun  
→ **Migrate otomatik çalışır**, ayrıca import gerekmez.

---

## SSH yoksa (manuel iskelet)

Plesk **PHP Composer** aracı varsa:

```bash
cd /var/www/vhosts/.../portal.kurtulum.com
composer create-project laravel/laravel _tmp "12.*" --no-dev
# _tmp icindekileri kok klasore tasiyin (app, vendor, public vb.)
# Sonra exportflow-kaynak.zip icerigini ustune yazin
composer install --no-dev
php artisan key:generate --force
chmod -R 775 storage bootstrap/cache
```

`.env` dosyasinda:

```env
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
APP_INSTALLED=false
```

---

## Sorun giderme

| Sorun | Çözüm |
|-------|--------|
| `/install` 500 | `php artisan key:generate --force` + `.env` içinde `SESSION_DRIVER=file` |
| `composer: command not found` | Plesk → PHP Composer etkinleştirin veya SSH |
| 500 devam | `public/plesk-check.php` açın |
| CSS yok | Document root = `public` |

---

## Özet

| Adım | Ne |
|------|-----|
| 1 | `plesk-kaynak-paket.bat` → ZIP |
| 2 | Plesk’e ZIP yükle |
| 3 | `bash scripts/plesk-iskelet-kur.sh` |
| 4 | Document root → `public` |
| 5 | `/install` → MySQL + admin |

Local veritabanını taşımak **isteğe bağlı**; sıfır kurulumda boş MySQL yeterli.
