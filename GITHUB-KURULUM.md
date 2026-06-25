# GitHub + Plesk — portal.kurtulum.com

## Önemli

- **Kurulum = bir kez** (`/install` sihirbazı)
- **Güncelleme = sürekli** (push → Pull → deploy, veritabanı korunur)

Detay: **[GUNCELLEME.md](GUNCELLEME.md)**

---

## İlk kurulum (bir kez)

1. Plesk **Git** → clone `https://github.com/valdrav/kurtulum.git`
2. Document root: **`public`**
3. MariaDB: `kurtulumportal_db` / `kurtulumportal_user`
4. `.env.plesk.example` → `.env` (File Manager), MariaDB bilgileri
5. Git → **Deploy** → Additional actions: `bash scripts/plesk-deploy.sh`
6. Vendor eksikse bir kez: **https://portal.kurtulum.com/plesk-composer.php**
7. **https://portal.kurtulum.com/install** → admin oluştur → bitti

`APP_INSTALLED=true` olduktan sonra `/install` bir daha açılmaz.

---

## Her kod güncellemesi

1. Cursor → GitHub **push**
2. Plesk → Git **Pull / Deploy**

Veritabanı silinmez. Kurulum tekrarlanmaz. Yeni migration varsa deploy otomatik uygular.

---

## GitHub'a gitmez

`.env`, `.env.plesk`, `vendor/`, `storage/logs/`

---

## Sık hatalar

| Hata | Çözüm |
|------|--------|
| Spatie eksik (ilk kurulum) | Deploy veya `plesk-composer.php` **bir kez** |
| Permission denied (Git) | [DEPLOY-PLESK.md](DEPLOY-PLESK.md) |
| 404 | Document root = `public` |
