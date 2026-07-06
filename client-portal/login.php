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
    } elseif ($urlError = api_url_looks_invalid($config)) {
        $error = 'API adresi hatalı: '.$urlError;
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
<div class="login-page">
    <div class="card login-card shadow-sm">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <div class="avatar avatar-lg bg-primary-lt text-primary mb-2 mx-auto"><i class="ti ti-building-store fs-2"></i></div>
                <h1 class="h3 mb-1"><?= e(app_name($config)) ?></h1>
                <p class="text-muted mb-0">Müşteri portalına giriş</p>
            </div>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="username" class="form-label">Kullanıcı adı</label>
                    <input type="text" id="username" name="username" class="form-control" autocomplete="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Şifre</label>
                    <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Giriş yap</button>
            </form>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
