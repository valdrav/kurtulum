# Client Portal (Düz PHP — ayrı subdomain)

Ana Ticari sistemine **HTTP API** ile bağlanır. Kendi veritabanı ve migration'ı **yoktur**.

## İki sunucu modeli

| Nerede | Ne var | Migration? |
|--------|--------|------------|
| **Ana domain** (ticari Laravel) | API, müşteri verisi, ayarlar | Evet, bir kez: `php artisan migrate` |
| **Subdomain** (client-portal PHP) | Giriş ekranı, API'den veri göster/düzenle | **Hayır** |

## Subdomain kurulumu (3 adım)

1. `client-portal/` klasörünün **tamamını** subdomain root'a kopyalayın.

2. `includes/config.php` düzenleyin:
   ```php
   'api_base_url' => 'https://log.kurtulum.com/api',  // ana siteniz — klasör değil, URL
   'api_token'    => 'ef_...',  // Ayarlar → Harici Sistem API
   ```

3. Tarayıcıda açın: `https://subdomain.com/check.php`  
   Tüm satırlar yeşil olunca `login.php` ile giriş yapın.

Varsayılan giriş: `admin` / `password` — hemen değiştirin.

## Ana sistemde (API tarafı)

1. `php artisan migrate` (henüz yapılmadıysa)
2. **Ayarlar → Harici Sistem API**
   - ☑ Harici API etkin → Kaydet
   - Yeni bağlantı: müşteri seç + izinler + oluştur
   - Gösterilen `ef_...` anahtarını subdomain `config.php`'ye yapıştır

## Bağlantı testi

`check.php` — giriş olmadan API durumunu gösterir. Canlıya aldıktan sonra silin veya erişimi kapatın.

## Dosyalar

```
client-portal/
  check.php       ← kurulum testi
  login.php
  dashboard.php
  customer.php
  directory.php
  includes/config.php   ← API adres + anahtar
```

`includes/` klasörü `.htaccess` ile web'den kapalıdır.
