# Güncelleme — portal.kurtulum.com

**Kurulum bir kez yapılır. Sonrasında sadece kod güncellersiniz; kurulum sihirbazına dönmez, veritabanı silinmez.**

---

## İki farklı işlem

| | İlk kurulum (1 kez) | Güncelleme (sürekli) |
|---|---|---|
| Ne zaman | Site ilk açıldığında | Her kod değişikliğinde |
| Siz | Cursor → push | Cursor → push |
| Plesk | Git Deploy + `/install` | Git **Pull / Deploy** |
| Veritabanı | Sihirbaz oluşturur | **Dokunulmaz** — sadece yeni migration |
| `.env` | Sunucuda oluşur | **Git ile gelmez** — ayarlar kalır |
| `/install` | Açılır | **Kapalı** (girişe yönlendirir) |
| Kullanıcılar / veriler | Admin oluşturulur | **Korunur** |

---

## Güncelleme akışı (her seferinde)

1. Bilgisayarda değişiklik yapın
2. Cursor ile **GitHub’a gönderin**
3. Plesk → **Git** → **Pull** veya **Deploy**

Deploy script (`scripts/plesk-deploy.sh`) otomatik yapar:

- `composer install` — eksik paketleri tamamlar (Spatie dahil)
- `php artisan migrate` — **sadece yeni tablo/kolon** ekler, veriyi silmez
- `php artisan optimize` — performans cache

**Kurulum sihirbazını tekrar açmanız gerekmez.**

---

## Plesk’te bir kez ayarlayın

**Git** → **Additional deploy actions**:

```
bash scripts/plesk-deploy.sh
```

Bundan sonra her Pull’da composer ve migration otomatik çalışır.

---

## Sunucuda kalıcı dosyalar (Git pull bunlara dokunmaz)

- `.env` — şifreler, `APP_INSTALLED=true`, MariaDB
- `storage/` — log, oturum, yüklenen dosyalar
- MariaDB — tüm iş verisi
- `vendor/` — Pull sonrası deploy script günceller (silinmez, yeniden kurulmaz)

---

## İlk kurulum bittikten sonra

1. `.env` içinde `APP_INSTALLED=true` kalır
2. `https://portal.kurtulum.com/install` → giriş sayfasına yönlendirir
3. Geçici dosyaları silin: `plesk-composer.php`, `plesk-check.php`, `ping.php`

---

## Sorun olursa (güncellemede)

| Durum | Ne yapın |
|-------|----------|
| Yeni sürüm hata veriyor | Plesk Deploy loguna bakın; genelde migration veya composer |
| Spatie / vendor eksik | Deploy script çalışmamış — Git Deploy tekrar |
| Veritabanı silmek | **Gerekmez** — sadece ilk kurulumda boş DB yeterli |

Veritabanını **sadece** sıfırdan baştan kurmak istiyorsanız phpMyAdmin’den tabloları silip `/install` açarsınız — normal güncellemede **yapmayın**.

---

## Özet

```
[Ilk kez]  push → deploy → /install → APP_INSTALLED=true → bitti

[Sonra]    push → deploy → site güncellendi (veri aynı)
```
