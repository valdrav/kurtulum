# GitHub + Plesk

**Akış:** Cursor’dan GitHub’a gönder → Plesk **Git** → **Pull** / **Deploy**

`vendor` GitHub'a gitmez; Pull sonrası Plesk deploy script `composer install` çalıştırır.

---

## Plesk — ilk kurulum (bir kez)

1. **Extensions** → **Git** → Install
2. `portal.kurtulum.com` → **Git** → **Clone**
   - URL: `https://github.com/valdrav/kurtulum.git`
   - Hedef: site kökü (`artisan` burada olacak)
3. **Hosting Settings** → document root: **`public`**
4. **Databases** → MariaDB: `kurtulumportal_db` / `kurtulumportal_user`
5. **File Manager** → `.env.plesk.example` → `.env` kopyala, MariaDB bilgilerini gir
6. **Git** → **Deploy**
7. **https://portal.kurtulum.com/install**

> Plesk **MariaDB** — `.env` içinde `DB_CONNECTION=mysql` doğrudur.

---

## Plesk — her güncelleme

1. Cursor’dan değişiklikleri GitHub’a gönder
2. Plesk → **Git** → **Pull** veya **Deploy**

**Additional deploy actions** (varsa): `bash scripts/plesk-deploy.sh`

---

## GitHub'a gitmez

| Dosya | Neden |
|-------|--------|
| `.env` / `.env.plesk` | Şifreler |
| `.env.plesk.example` | Şablon (GitHub'a gider) |
| `vendor/` | Sunucuda composer |

---

## Sık hatalar

| Hata | Çözüm |
|------|--------|
| **unable to unlink Permission denied** | [DEPLOY-PLESK.md](DEPLOY-PLESK.md) → Git izinleri — File Manager sahiplik + Git Remove/Clone |
| 404 (Plesk sayfası) | Document root = `public` |
| AH00124 redirect | Kök `.htaccess` sil |
| 403 ModSecurity | Web Application Firewall → Kapalı |
| Log Permission denied | File Manager → `storage` → izinler, alt dizinlere uygula |
| sqlite hatası | `.env` → MariaDB, `APP_INSTALLED=false` |
| Spatie Permission not found | Plesk Git → **Deploy** (composer install) — Gereksinimler sayfasında vendor satırı OK olmalı |

Detay: [DEPLOY-PLESK.md](DEPLOY-PLESK.md)
