# Plesk Kurulum — portal.kurtulum.com (En Kolay Yol)

## Özet: 7 adım, ~15 dakika

| # | Ne yapacaksınız |
|---|-----------------|
| 1 | Bilgisayarda `plesk-paket.bat` çalıştır → `portal-kurtulum.zip` oluşur |
| 2 | Plesk'te `portal.kurtulum.com` alt alan adı aç |
| 3 | ZIP'i site klasörüne yükle, çıkart |
| 4 | Document root → **`public`** |
| 5 | MySQL veritabanı oluştur |
| 6 | `storage` + `bootstrap/cache` yazılabilir yap (775) |
| 7 | Tarayıcıda **`/install`** sihirbazını tamamla |

---

## 1. ZIP paketi hazırlayın (Windows)

Proje klasöründe çift tıklayın:

```
plesk-paket.bat
```

Oluşan dosya: **`portal-kurtulum.zip`** (vendor dahil, sunucuda Composer gerekmez)

---

## 2. Plesk — alt alan adı

1. **Websites & Domains** → **Add Subdomain**
2. Subdomain: `portal` → Domain: `kurtulum.com`
3. Sonuç: `portal.kurtulum.com`

---

## 3. Dosyaları yükleyin

1. **Files** → site kök klasörü (genelde `portal.kurtulum.com` veya `httpdocs`)
2. **Upload** → `portal-kurtulum.zip`
3. ZIP'e sağ tık → **Extract**

Klasör yapısı şöyle olmalı (doğrudan kökte):

```
httpdocs/
  app/
  public/        ← index.php burada
  vendor/
  .env           ← ZIP ile gelir
  ...
```

> ZIP içinde alt klasör açıldıysa (ör. `ticari/app/...`), dosyaları bir üst klasöre taşıyın.

---

## 4. Document Root (ÖNEMLİ)

**Hosting Settings** → **Document root**:

```
public
```

veya tam yol: `/var/www/vhosts/kurtulum.com/portal.kurtulum.com/public`

Kaydet.

---

## 5. PHP sürümü

**PHP Settings** → **8.2** veya **8.3** seçin.

Gerekli eklentiler (Plesk'te genelde açıktır): `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `json`, `curl`, `fileinfo`, `zip`

---

## 6. MySQL veritabanı

**Databases** → **Add Database**:

| Alan | Örnek |
|------|--------|
| Veritabanı adı | `kurtulum_portal` |
| Kullanıcı | `kurtulum_user` |
| Şifre | güçlü şifre (not alın) |

---

## 7. Klasör izinleri

**Files** → şu klasörlere sağ tık → **Change Permissions** → **775**:

- `storage` (alt klasörler dahil)
- `bootstrap/cache`

---

## 8. Kurulum sihirbazı

1. SSL açın: **SSL/TLS** → Let's Encrypt → `portal.kurtulum.com`
2. Tarayıcıda açın: **https://portal.kurtulum.com/install**
3. Adımları izleyin:
   - Gereksinimler ✓
   - **Veritabanı**: MySQL seçin, Plesk'teki DB bilgilerini girin
   - **Site URL**: `https://portal.kurtulum.com`
   - **Admin hesabı**: e-posta ve şifre belirleyin

Bitti. Giriş: **https://portal.kurtulum.com/login**

---

## 9. Cron (kur senkronu için)

**Scheduled Tasks** → **Add Task**:

| Alan | Değer |
|------|--------|
| Sıklık | Her dakika `* * * * *` |
| Komut | aşağıdaki satır |

```bash
cd /var/www/vhosts/kurtulum.com/portal.kurtulum.com && /opt/plesk/php/8.3/bin/php artisan schedule:run >> /dev/null 2>&1
```

> PHP yolunu **PHP Settings** ekranından kontrol edin (`8.2` kullanıyorsanız yolu ona göre değiştirin).

---

## SSH varsa (opsiyonel)

Web sihirbazı yerine terminal:

```bash
cd /var/www/vhosts/kurtulum.com/portal.kurtulum.com
# .env icinde DB bilgilerini duzenleyin
bash scripts/plesk-kur.sh
```

---

## Sorun giderme

### 500 hatası — Laravel iskeleti kurulmaz

**Hayır**, Plesk’te ayrıca `composer create-project` veya Laravel iskeleti kurmanız gerekmez.  
Tüm proje (app, vendor, public…) **portal-kurtulum.zip** ile gelir.

500 genelde şu nedenlerden olur:

| Sıra | Kontrol | Çözüm |
|------|---------|--------|
| 1 | Document root | **Hosting Settings** → `public` (httpdocs değil) |
| 2 | Klasör yapısı | `app/`, `vendor/`, `public/index.php` site kökünde mi? ZIP alt klasörde açıldıysa dosyaları yukarı taşıyın |
| 3 | vendor | `vendor/autoload.php` var mı? Yoksa ZIP’i yeniden yükleyin veya SSH: `composer install --no-dev` |
| 4 | .env | Kökte `.env` var mı? Yoksa `.env.plesk` → `.env` olarak kopyalayın |
| 5 | İzinler | `storage` ve `bootstrap/cache` → **775** |
| 6 | PHP | **8.2+**, `pdo_mysql` açık |
| 7 | APP_KEY | Boşsa önce **/install** açın (sihirbaz key üretir) |

**Teşhis:** Tarayıcıda açın (geçici):

```
https://portal.kurtulum.com/plesk-check.php
```

Hangi satır HATA ise onu düzeltin. Sonra `plesk-check.php` dosyasını silin.

**Log:** Plesk File Manager → `storage/logs/laravel.log` (son satırlar gerçek hatayı gösterir).

Geçici olarak hatayı görmek için `.env` içinde `APP_DEBUG=true` yapıp sayfayı yenileyin — **düzeltince tekrar `false` yapın.**

### Diğer sorunlar

---

## Kontrol listesi

- [ ] `plesk-paket.bat` → ZIP oluşturuldu
- [ ] ZIP sunucuya yüklendi ve açıldı
- [ ] Document root = `public`
- [ ] PHP 8.2+
- [ ] MySQL oluşturuldu
- [ ] storage + bootstrap/cache → 775
- [ ] SSL (HTTPS) aktif
- [ ] `/install` tamamlandı
- [ ] Cron tanımlandı
