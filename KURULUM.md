# ExportFlow ERP - Kurulum

**Canlı sunucu (Plesk):** [GITHUB-KURULUM.md](GITHUB-KURULUM.md) — `git push` → Plesk Pull → `bash scripts/plesk-deploy.sh`

**Yerel geliştirme:** `baslat.bat` veya `php artisan serve`

## Gereksinimler
- PHP 8.3+
- MySQL 5.7+ / MariaDB 10.3+
- Apache veya Nginx (mod_rewrite)
- PDO, OpenSSL, Mbstring, cURL, JSON

## Plesk Kurulum Adımları

### 1. Subdomain Oluşturma
1. Plesk Panel → **Websites & Domains** → **Add Subdomain**
2. Subdomain adını girin (örn: `erp.sirketiniz.com`)
3. Document Root: `/httpdocs/public` veya `/subdomain/public`

### 2. Kod (Git)

Plesk'te Git ile clone/pull yapın. Ayrıntı: [GITHUB-KURULUM.md](GITHUB-KURULUM.md)

1. Document root: **`public`**
2. Site kökünde **`.htaccess` olmamalı** (AH00124 döngüsü)

### 3. PHP Ayarları
Plesk → **PHP Settings**:
- PHP Version: **8.3**
- `memory_limit`: 256M
- `upload_max_filesize`: 32M
- `post_max_size`: 32M
- `max_execution_time`: 120

### 4. İzinler
Aşağıdaki klasörler yazılabilir olmalı (755 veya 775):
```
storage/
bootstrap/cache/
```

### 5. Veritabanı
1. Plesk → **Databases** → Yeni veritabanı oluşturun
2. Kullanıcı oluşturup tüm yetkileri verin

### 6. Web Kurulum Sihirbazı
1. Tarayıcıda `https://erp.sirketiniz.com/install` adresine gidin
2. Sistem gereksinimlerini kontrol edin
3. Veritabanı bilgilerini girin
4. Admin hesabını oluşturun
5. Kurulum tamamlandı!

> **Terminal gerekmez** - Tüm kurulum web arayüzünden yapılır.

### 7. Güvenlik (Kurulum Sonrası)
- `APP_DEBUG=false` yapın (.env)
- Kurulum tamamlandıktan sonra `/install` rotası otomatik devre dışı kalır

## AI Özellikleri
`.env` dosyasına ekleyin:
```
AI_API_KEY=sk-your-openai-key
AI_MODEL=gpt-4o-mini
```

## Güncelleme
**Ayarlar → Güncellemeler** menüsünden ZIP paketi yükleyerek tek tık güncelleme yapabilirsiniz.

## Modüller
- Dashboard, CRM, Tedarikçi, Sipariş
- Lojistik (TIR/Kara, Deniz, Hava, Demir, Multimodal)
- Finans & Cari Hesap, Gelir/Gider
- Evrak Yönetimi, Görev & Takvim
- Personel, Raporlama, E-posta, AI Asistan

## Destek
ExportFlow ERP v1.0.0 | Laravel 12
