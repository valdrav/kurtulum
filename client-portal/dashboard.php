<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

if (($apiError = refresh_api_context($api)) !== null) {
    flash('error', $apiError);
}

$me = $_SESSION['api_me'] ?? null;
$perms = api_permissions();
$activeNav = 'dashboard';
$pageTitle = 'Panel — '.app_name($config);

ob_start();
?>
<h1>Panel</h1>

<?php if (! is_array($me)): ?>
<div class="alert err">API bağlantı bilgisi alınamadı.</div>
<?php else: ?>
<p class="meta">Bağlantı: <strong><?= e($me['connection'] ?? '—') ?></strong></p>

<div class="card-grid">
    <?php if (! empty($perms['customer'])): ?>
    <div class="card">
        <a href="customer.php">Müşteri bilgileri</a>
        <p class="meta"><?= e($me['customer']['company_name'] ?? '') ?></p>
        <?php if (! empty($perms['edit_customer'])): ?><p class="meta">Düzenleme açık</p><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (! empty($perms['directory'])): ?>
    <div class="card">
        <a href="directory.php">Rehber</a>
        <p class="meta">Kişi listesi<?= ! empty($perms['edit_directory']) ? ' · düzenleme açık' : ' · salt okunur' ?></p>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($perms['customer']) && empty($perms['directory'])): ?>
<div class="alert err">Bu API anahtarı için görüntüleme izni tanımlı değil. Ana sistemden izinleri kontrol edin.</div>
<?php endif; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
