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
    $response = $api->patch('/customer', [
        'contact_person' => trim((string) ($_POST['contact_person'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'city' => trim((string) ($_POST['city'] ?? '')),
        'address' => trim((string) ($_POST['address'] ?? '')),
    ]);
    if ($response['status'] === 200) {
        flash('success', 'Müşteri bilgileri kaydedildi.');
        redirect('customer.php');
    }
    flash('error', api_error_message($response, 'Kayıt başarısız'));
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
<?= page_actions('Müşteri bilgileri') ?>

<form method="post">
    <?= csrf_field() ?>
    <div class="card mb-3"><div class="card-body">
        <dl class="row detail-grid mb-3">
            <dt class="col-sm-3">Firma</dt><dd class="col-sm-9 fw-semibold"><?= e($customer['company_name'] ?? '') ?></dd>
            <dt class="col-sm-3">Tip / Durum</dt><dd class="col-sm-9"><?= e(($customer['type'] ?? '—').' · '.($customer['status'] ?? '')) ?></dd>
            <dt class="col-sm-3">Ülke / Vergi no</dt><dd class="col-sm-9"><?= e(($customer['country'] ?? '—').' · '.($customer['tax_number'] ?? '—')) ?></dd>
            <dt class="col-sm-3">Para birimi</dt><dd class="col-sm-9"><?= e($customer['currency'] ?? '') ?></dd>
        </dl>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Yetkili kişi</label><input type="text" name="contact_person" class="form-control" value="<?= e($customer['contact_person'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>></div>
            <div class="col-md-6"><label class="form-label">E-posta</label><input type="email" name="email" class="form-control" value="<?= e($customer['email'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>></div>
            <div class="col-md-6"><label class="form-label">Telefon</label><input type="text" name="phone" class="form-control" value="<?= e($customer['phone'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>></div>
            <div class="col-md-6"><label class="form-label">Şehir</label><input type="text" name="city" class="form-control" value="<?= e($customer['city'] ?? '') ?>" <?= $canEdit ? '' : 'readonly' ?>></div>
            <div class="col-12"><label class="form-label">Adres</label><textarea name="address" class="form-control" rows="3" <?= $canEdit ? '' : 'readonly' ?>><?= e($customer['address'] ?? '') ?></textarea></div>
        </div>
    </div></div>
    <div class="d-flex gap-2">
        <?php if ($canEdit): ?><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Kaydet</button><?php else: ?><span class="text-muted">Salt okunur</span><?php endif; ?>
        <a href="dashboard.php" class="btn btn-outline-secondary">Geri</a>
    </div>
</form>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
