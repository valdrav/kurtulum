@echo off
setlocal EnableDelayedExpansion
chcp 65001 >nul
title Plesk Paketi Olustur
cd /d "%~dp0"

where composer >nul 2>&1
if errorlevel 1 (
    echo [UYARI] Composer PATH'te yok. Mevcut vendor klasoru kullanilacak.
) else (
    echo [1/4] Bagimliliklar yukleniyor...
    composer install --no-dev --optimize-autoloader --no-interaction
    if errorlevel 1 (
        echo HATA: composer install basarisiz.
        pause
        exit /b 1
    )
)

if not exist "vendor\" (
    echo HATA: vendor klasoru yok. Once composer install calistirin.
    pause
    exit /b 1
)

echo [2/4] Gecici .env hazirlaniyor...
set HAD_ENV=0
if exist ".env" (
    set HAD_ENV=1
    ren ".env" ".env.local.bak"
)
copy /Y ".env.plesk" ".env" >nul

where php >nul 2>&1
if not errorlevel 1 (
    echo [2b/4] APP_KEY uretiliyor...
    php artisan key:generate --force --no-interaction >nul 2>&1
)

echo [3/4] ZIP olusturuluyor (1-2 dk surebilir)...
set OUT=portal-kurtulum.zip
if exist "%OUT%" del /f "%OUT%"

tar -a -c -f "%OUT%" ^
  --exclude=node_modules ^
  --exclude=.git ^
  --exclude=.env.local.bak ^
  --exclude=database/database.sqlite ^
  --exclude=storage/logs/*.log ^
  --exclude=storage/framework/cache/data ^
  --exclude=storage/framework/sessions ^
  --exclude=storage/framework/views ^
  --exclude=storage/pail ^
  --exclude=.phpunit.cache ^
  --exclude=.env.plesk ^
  --exclude=.env.example ^
  --exclude=portal-kurtulum.zip ^
  --exclude=tests ^
  .

echo [4/4] Yerel .env geri yukleniyor...
del /f ".env" 2>nul
if "!HAD_ENV!"=="1" ren ".env.local.bak" ".env"

if not exist "%OUT%" (
    echo HATA: ZIP olusturulamadi.
    pause
    exit /b 1
)

echo [+] Yerel gelistirme bagimliliklari geri yukleniyor...
composer install --no-interaction >nul 2>&1

echo.
echo ========================================
echo  HAZIR: %CD%\%OUT%
echo ========================================
echo.
echo Plesk adimlari (DEPLOY-PLESK.md):
echo  1. portal.kurtulum.com alt alan adi
echo  2. ZIP yukle ve ac
echo  3. Document root: public
echo  4. PHP 8.2+
echo  5. MySQL veritabani olustur
echo  6. storage + bootstrap/cache -^> 775
echo  7. https://portal.kurtulum.com/install
echo.
pause
