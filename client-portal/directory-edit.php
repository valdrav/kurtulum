<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

if (! can('edit_directory')) {
    http_response_code(403);
    exit('Rehber düzenleme izniniz yok.');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$contact = ['first_name' => '', 'last_name' => '', 'phone' => '', 'description' => ''];

if ($isEdit) {
    $response = $api->get('/directory/'.$id);
    if ($response['status'] !== 200 || ! is_array($response['body']['data'] ?? null)) {
        flash('error', 'Kayıt bulunamadı.');
        redirect('directory.php');
    }
    $contact = $response['body']['data'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $payload = [
        'first_name' => trim((string) ($_POST['first_name'] ?? '')),
        'last_name' => trim((string) ($_POST['last_name'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];

    if ($payload['first_name'] === '' || $payload['last_name'] === '' || $payload['phone'] === '') {
        flash('error', 'Ad, soyad ve telefon zorunludur.');
        redirect('directory-edit.php'.($isEdit ? '?id='.$id : ''));
    }

    $response = $isEdit ? $api->put('/directory/'.$id, $payload) : $api->post('/directory', $payload);

    if (in_array($response['status'], [200, 201], true)) {
        flash('success', $isEdit ? 'Kayıt güncellendi.' : 'Kayıt eklendi.');
        redirect('directory.php');
    }
    flash('error', api_error_message($response, 'Kayıt başarısız'));
    redirect('directory-edit.php'.($isEdit ? '?id='.$id : ''));
}

$activeNav = 'directory';
$pageTitle = ($isEdit ? 'Kişi düzenle' : 'Yeni kişi').' — '.app_name($config);

ob_start();
?>
<?= page_actions($isEdit ? 'Kişi düzenle' : 'Yeni kişi') ?>

<form method="post">
    <?= csrf_field() ?>
    <div class="card mb-3"><div class="card-body">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Ad *</label><input type="text" name="first_name" class="form-control" value="<?= e($contact['first_name'] ?? '') ?>" required></div>
            <div class="col-md-6"><label class="form-label">Soyad *</label><input type="text" name="last_name" class="form-control" value="<?= e($contact['last_name'] ?? '') ?>" required></div>
            <div class="col-md-6"><label class="form-label">Telefon *</label><input type="text" name="phone" class="form-control" value="<?= e($contact['phone'] ?? '') ?>" required></div>
            <div class="col-12"><label class="form-label">Açıklama</label><textarea name="description" class="form-control" rows="3"><?= e($contact['description'] ?? '') ?></textarea></div>
        </div>
    </div></div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Kaydet</button>
        <a href="directory.php" class="btn btn-outline-secondary">İptal</a>
    </div>
</form>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
