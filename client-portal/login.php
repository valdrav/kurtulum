<?php

require __DIR__.'/includes/bootstrap.php';

if (Auth::user()) {
    redirect('dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (Auth::isLocked()) {
        $error = Auth::lockMessage();
    } elseif ($username === '' || $password === '') {
        $error = 'Kullanıcı adı ve şifre gerekli.';
    } elseif (! Auth::attempt($config, $username, $password)) {
        $error = Auth::isLocked() ? Auth::lockMessage() : 'Geçersiz kullanıcı adı veya şifre.';
    } elseif ($tokenError = api_token_looks_invalid($config)) {
        $error = $tokenError;
    } elseif ($apiError = refresh_api_context($api)) {
        Auth::logout();
        $error = $apiError;
    } else {
        redirect('dashboard.php');
    }
}

$pageTitle = 'Giriş — '.app_name($config);
ob_start();
?>
<div class="login-box">
    <h1><?= e(app_name($config)) ?></h1>
    <p class="meta" style="text-align:center;margin-bottom:1.25rem">Güvenli giriş</p>
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="field">
            <label for="username">Kullanıcı adı</label>
            <input type="text" id="username" name="username" autocomplete="username" required autofocus>
        </div>
        <div class="field">
            <label for="password">Şifre</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn" style="width:100%">Giriş yap</button>
    </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
