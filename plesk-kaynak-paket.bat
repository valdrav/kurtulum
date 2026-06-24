@echo off
setlocal EnableDelayedExpansion
chcp 65001 >nul
title ExportFlow Kaynak Paketi (Plesk iskelet icin)
cd /d "%~dp0"

set OUT=exportflow-kaynak.zip
if exist "%OUT%" del /f "%OUT%"

echo Kaynak paketi olusturuluyor (vendor YOK - sunucuda composer calisacak)...

tar -a -c -f "%OUT%" ^
  --exclude=node_modules ^
  --exclude=vendor ^
  --exclude=.git ^
  --exclude=.env ^
  --exclude=.env.local.bak ^
  --exclude=database/database.sqlite ^
  --exclude=storage/logs/*.log ^
  --exclude=storage/framework/cache/data ^
  --exclude=storage/framework/sessions/* ^
  --exclude=storage/framework/views/* ^
  --exclude=storage/pail ^
  --exclude=.phpunit.cache ^
  --exclude=exportflow-kaynak.zip ^
  --exclude=portal-kurtulum.zip ^
  --exclude=tests ^
  app bootstrap config database lang modules public resources routes scripts ^
  composer.json composer.lock .env.plesk artisan

echo.
echo HAZIR: %CD%\%OUT%
echo.
echo Sonraki adim: PLESK-ISKELET-KURULUM.md dosyasini okuyun.
pause
