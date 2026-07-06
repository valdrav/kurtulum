<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

if (! can('customer')) {
    http_response_code(403);
    exit('Müşteri görüntüleme izniniz yok.');
}

$canEdit = can('edit_customer');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    verify_csrf();
    $payload = [
        'contact_person' => trim((string) ($_POST['contact_person'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'city' => trim((string) ($_POST['city'] ?? '')),
        'address' => trim((string) ($_POST['address'] ?? '')),
    ];

    $response = $api->patch('/customer', $payload);
    if ($response['status'] === 200) {
        flash('success', 'Müşteri bilgileri kaydedildi.');
        redirect('customer.php');
    }

    $message = is_array($response['body']) ? ($response['body']['message'] ?? null) : null;
    flash('error', $message ?: 'Kayıt başarısız (HTTP '.$response['status'].').');
    redirect('customer.php');
}

$response = $api->get('/customer');
if ($response['status'] !== 200 || ! is_array($response['body']['data'] ?? null)) {
    flash('error', 'Müşteri verisi alınamadı.');
    redirect('dashboard.php');
}

$customer = $response['body']['data'];
$activeNav = 'customer';
$pageTitle = 'Müşteri — '.app_name($config);

ob_start();
?>
<h1>Müşteri bilgileri</h1>

<form method="post">
    <?= csrf_field() ?>

    <div class="field">
        <label>Firma adı</label>
        <input type="text" value="<?= e($customer['company_name'] ?? '') ?>" class="readonly" readonly>
    </div>
    <div class="field">
        <label for="contact_person">Yetkili kişi</label>
        <input type="text" id="contact_person" name="contact_person" value="<?= e($customer['contact_person'] ?? '') ?>" <?= $canEdit ? '' : 'readonly class="readonly"' ?>>
    </div>
    <div class="field">
        <label for="email">E-posta</label>
        <input type="email" id="email" name="email" value="<?= e($customer['email'] ?? '') ?>" <?= $canEdit ? '' : 'readonly class="readonly"' ?>>
    </div>
    <div class="field">
        <label for="phone">Telefon</label>
        <input type="text" id="phone" name="phone" value="<?= e($customer['phone'] ?? '') ?>" <?= $canEdit ? '' : 'readonly class="readonly"' ?>>
    </div>
    <div class="field">
        <label for="city">Şehir</label>
        <input type="text" id="city" name="city" value="<?= e($customer['city'] ?? '') ?>" <?= $canEdit ? '' : 'readonly class="readonly"' ?>>
    </div>
    <div class="field">
        <label for="address">Adres</label>
        <textarea id="address" name="address" <?= $canEdit ? '' : 'readonly class="readonly"' ?>><?= e($customer['address'] ?? '') ?></textarea>
    </div>

    <div class="field">
        <label>Durum / Para birimi</label>
        <input type="text" value="<?= e(($customer['status'] ?? '').' · '.($customer['currency'] ?? '')) ?>" class="readonly" readonly>
    </div>

    <div class="actions">
        <?php if ($canEdit): ?>
        <button type="submit" class="btn">Kaydet</button>
        <?php else: ?>
        <p class="meta">Bu bağlantıda düzenleme izni kapalı — salt okunur.</p>
        <?php endif; ?>
        <a href="dashboard.php" class="btn secondary">Geri</a>
    </div>
</form>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
