<?php

$bootstrap = __DIR__.'/includes/bootstrap.php';
if (! is_file($bootstrap)) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Dosya eksik</h1>';
    echo '<p><code>includes/</code> klasörü yüklenmemiş. FTP ile <strong>client-portal</strong> klasörünün <em>içeriğinin tamamını</em> yükleyin:</p>';
    echo '<ul><li>includes/ (bootstrap.php, config.php, …)</li><li>assets/</li><li>index.php, login.php, check.php, …</li></ul>';
    echo '<p>Sadece index.php yüklemek yetmez.</p>';
    exit;
}

require $bootstrap;

if (Auth::user()) {
    redirect('dashboard.php');
}

redirect('login.php');
