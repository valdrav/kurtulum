<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    if (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

$configPath = __DIR__.'/config.php';
$samplePath = __DIR__.'/config.sample.php';

if (! is_file($configPath) && is_file($samplePath)) {
    copy($samplePath, $configPath);
}

if (! is_file($configPath)) {
    http_response_code(500);
    echo '<h1>Yapılandırma eksik</h1><p><code>includes/config.php</code> bulunamadı.</p>';
    exit;
}

$config = require $configPath;

require __DIR__.'/helpers.php';
require __DIR__.'/Auth.php';
require __DIR__.'/ApiClient.php';

$api = new ApiClient($config['api_base_url'], $config['api_token']);
