# XAMPP Apache'yi Laragon PHP 8.3 ile calistirir (localhost/ticari/public icin)
$ErrorActionPreference = 'Stop'

$conf = 'C:\xampp\apache\conf\extra\httpd-xampp.conf'
if (-not (Test-Path $conf)) {
    Write-Host "XAMPP bulunamadi: $conf" -ForegroundColor Red
    exit 1
}

$phpRoot = Get-ChildItem 'C:\laragon\bin\php' -Directory -ErrorAction SilentlyContinue |
    Where-Object { $_.Name -like 'php-8.3*' } |
    Sort-Object Name -Descending |
    Select-Object -First 1

if (-not $phpRoot) {
    Write-Host "Laragon PHP 8.3 bulunamadi. Laragon kurulu olmali." -ForegroundColor Red
    exit 1
}

$phpPath = ($phpRoot.FullName -replace '\\', '/')
Write-Host "Kullanilacak PHP: $phpPath" -ForegroundColor Cyan

$backup = "$conf.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
Copy-Item $conf $backup
Write-Host "Yedek alindi: $backup" -ForegroundColor Gray

$content = Get-Content $conf -Raw
$content = $content -replace 'LoadFile "C:/xampp/php/php8ts\.dll"', "LoadFile `"$phpPath/php8ts.dll`""
$content = $content -replace 'LoadFile "C:/xampp/php/libpq\.dll"', "LoadFile `"$phpPath/libpq.dll`""
$content = $content -replace 'LoadFile "C:/xampp/php/libsqlite3\.dll"', "LoadFile `"$phpPath/libsqlite3.dll`""
$content = $content -replace 'LoadModule php_module "C:/xampp/php/php8apache2_4\.dll"', "LoadModule php_module `"$phpPath/php8apache2_4.dll`""
$content = $content -replace 'PHPINIDir "C:/xampp/php"', "PHPINIDir `"$phpPath`""
$content = $content -replace 'SetEnv PHPRC "\\\\xampp\\\\php"', "SetEnv PHPRC `"$($phpRoot.FullName)`""

Set-Content -Path $conf -Value $content -NoNewline

Write-Host ""
Write-Host "Tamam! Simdi XAMPP Control Panel'den Apache'yi Stop -> Start yapin." -ForegroundColor Green
Write-Host "Sonra acin: http://localhost/ticari/public/install" -ForegroundColor Green
