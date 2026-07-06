<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

if (! can('edit_directory')) {
    http_response_code(403);
    exit('Rehber düzenleme izniniz yok.');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$contact = [
    'first_name' => '',
    'last_name' => '',
    'phone' => '',
    'description' => '',
];

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

    $response = $isEdit
        ? $api->put('/directory/'.$id, $payload)
        : $api->post('/directory', $payload);

    if (in_array($response['status'], [200, 201], true)) {
        flash('success', $isEdit ? 'Kayıt güncellendi.' : 'Kayıt eklendi.');
        redirect('directory.php');
    }

    flash('error', 'Kayıt başarısız (HTTP '.$response['status'].').');
    redirect('directory-edit.php'.($isEdit ? '?id='.$id : ''));
}

$activeNav = 'directory';
$pageTitle = ($isEdit ? 'Düzenle' : 'Yeni kişi').' — '.app_name($config);

ob_start();
?>
<h1><?= $isEdit ? 'Kişi düzenle' : 'Yeni kişi' ?></h1>

<form method="post">
    <?= csrf_field() ?>
    <div class="field">
        <label for="first_name">Ad *</label>
        <input type="text" id="first_name" name="first_name" value="<?= e($contact['first_name'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="last_name">Soyad *</label>
        <input type="text" id="last_name" name="last_name" value="<?= e($contact['last_name'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="phone">Telefon *</label>
        <input type="text" id="phone" name="phone" value="<?= e($contact['phone'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="description">Açıklama</label>
        <textarea id="description" name="description"><?= e($contact['description'] ?? '') ?></textarea>
    </div>
    <div class="actions">
        <button type="submit" class="btn">Kaydet</button>
        <a href="directory.php" class="btn secondary">İptal</a>
    </div>
</form>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
