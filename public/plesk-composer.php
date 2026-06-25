<?php
/**
 * Gecici — Spatie/vendor eksikse bir kez calistirin, sonra silin.
 * https://portal.kurtulum.com/plesk-composer.php
 */
header('Content-Type: text/html; charset=utf-8');

$root = dirname(__DIR__);
chdir($root);

if (is_file($root . '/.env') && str_contains((string) file_get_contents($root . '/.env'), 'APP_INSTALLED=true')) {
    http_response_code(403);
    exit('<p>Kurulum tamamlandi. Bu dosyayi (public/plesk-composer.php) silin.</p>');
}

$php = PHP_BINARY ?: 'php';
$composer = null;

if ($bin = trim((string) @shell_exec('command -v composer 2>/dev/null'))) {
    $composer = $bin;
} elseif (is_file('/usr/local/bin/composer')) {
    $composer = "$php /usr/local/bin/composer";
} elseif (is_file('/usr/bin/composer')) {
    $composer = "$php /usr/bin/composer";
} elseif (is_file('/opt/psa/var/modules/composer/composer.phar')) {
    $composer = "$php /opt/psa/var/modules/composer/composer.phar";
}

echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>Composer Kurulum</title>';
echo '<style>body{font-family:system-ui;max-width:720px;margin:2rem auto;padding:0 1rem}pre{background:#111;color:#eee;padding:1rem;overflow:auto;font-size:13px}.ok{color:green}.err{color:#c00}</style></head><body>';
echo '<h1>Composer — portal.kurtulum.com</h1>';

$spatieOk = is_file($root . '/vendor/spatie/laravel-permission/src/Models/Permission.php');

if ($spatieOk) {
    echo '<p class="ok"><strong>Spatie paketleri zaten yuklu.</strong></p>';
    echo '<p><a href="/install/requirements">Kurulum gereksinimlerine don</a></p>';
    echo '<p><small>Guvenlik icin public/plesk-composer.php dosyasini silin.</small></p></body></html>';
    exit;
}

if (! $composer) {
    echo '<p class="err">Composer bulunamadi.</p>';
    echo '<p>Plesk panelden: <strong>Websites &amp; Domains</strong> → <strong>portal.kurtulum.com</strong> → <strong>PHP Composer</strong> → <strong>Install Dependencies</strong></p>';
    echo '</body></html>';
    exit;
}

if (! is_file($root . '/composer.json')) {
    echo '<p class="err">composer.json bulunamadi. Git deploy dogru klasore mi?</p></body></html>';
    exit;
}

$cmd = $composer . ' install --no-dev --optimize-autoloader --no-interaction 2>&1';
echo '<p>Calistiriliyor: <code>' . htmlspecialchars($cmd) . '</code></p><pre>';

$lines = [];
$code = 1;
if (function_exists('proc_open')) {
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
    if (is_resource($proc)) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        $lines = array_filter(explode("\n", $stdout . "\n" . $stderr));
    }
} elseif (function_exists('exec')) {
    exec($cmd, $lines, $code);
}

echo htmlspecialchars(implode("\n", $lines));
echo '</pre>';

$spatieOk = is_file($root . '/vendor/spatie/laravel-permission/src/Models/Permission.php');

if ($code === 0 && $spatieOk) {
    echo '<p class="ok"><strong>Basarili.</strong> Spatie paketleri yuklendi.</p>';
    echo '<p><a href="/install/requirements">Kurulum gereksinimlerine devam et</a></p>';
} else {
    echo '<p class="err"><strong>Hata.</strong> Plesk → PHP Composer → Install Dependencies deneyin veya hosting destegine yazin.</p>';
}

echo '<p><small>Islem bitince public/plesk-composer.php dosyasini silin.</small></p></body></html>';
