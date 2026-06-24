@echo off
title ExportFlow ERP
set PHP=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe

if not exist "%PHP%" (
    echo Laragon PHP 8.3 bulunamadi: %PHP%
    echo Laragon kurulu mu kontrol edin veya XAMPP-PHP83.bat calistirin.
    pause
    exit /b 1
)

cd /d "%~dp0"
echo.
echo Veritabani guncelleniyor...
"%PHP%" artisan migrate --force 2>nul
echo Depolama baglantisi kontrol ediliyor...
"%PHP%" artisan storage:link 2>nul
echo.
echo ExportFlow ERP baslatiliyor...
echo Tarayici: http://127.0.0.1:8000
echo Durdurmak icin bu pencerede Ctrl+C
echo.

start "" "http://127.0.0.1:8000/login"
"%PHP%" artisan serve --host=127.0.0.1 --port=8000
