# log.kurtulum.com — Düz PHP panel kurulumu

Sizin modeliniz doğru: **log.kurtulum.com** = `client-portal/` klasörünün tamamı.

Laravel ana sistem **başka adreste** kalır (genelde `portal.kurtulum.com`). Panel, oradaki API’ye bağlanır.

```
portal.kurtulum.com  →  Laravel (Ticari + /api/me)
log.kurtulum.com     →  client-portal (düz PHP, sadece dosya)
```

---

## FTP / Plesk — log.kurtulum.com

**Document root:** site kökü (public alt klasörü yok)

**Yüklenecek:** `client-portal/` içindeki **her şey** — tek tek index.php değil:

```
log.kurtulum.com/
├── index.php
├── login.php
├── check.php
├── dashboard.php
├── customer.php
├── directory.php
├── directory-edit.php
├── logout.php
├── .htaccess
├── assets/
│   └── style.css
└── includes/          ← MUTLAKA
    ├── bootstrap.php
    ├── config.php
    ├── Auth.php
    ├── ApiClient.php
    ├── helpers.php
    ├── layout.php
    └── .htaccess
```

### 500 hatası: `includes/bootstrap.php` yok

Sadece `index.php` atılmış, `includes/` atılmamış demektir. Klasörün tamamını yeniden yükleyin.

---

## config.php (includes/config.php)

```php
'api_base_url' => 'https://portal.kurtulum.com/api',  // Laravel siteniz
'api_token'    => 'ef_...',                           // Ayarlar → Harici API
```

**api_base_url log.kurtulum.com olmamalı** — log’da Laravel yok, sadece panel var.

Laravel farklı domaindeyse sadece o adresi yazın.

---

## Test

1. `https://portal.kurtulum.com/api/ping` → `{"ok":true}` (Laravel tarafı)
2. `https://log.kurtulum.com/check.php` → bağlantı testi
3. `https://log.kurtulum.com/login.php` → giriş (`admin` / `password`)

---

## Laravel tarafı (portal veya ana domain)

- Harici API etkin (Ayarlar)
- Bağlantı + müşteri + anahtar
- Güncel `routes/web.php`, `routes/external-api.php` deploy
- `php artisan route:clear`
