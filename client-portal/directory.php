<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

if (! can('directory')) {
    http_response_code(403);
    exit('Rehber görüntüleme izniniz yok.');
}

$canEdit = can('edit_directory');
$search = trim((string) ($_GET['search'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit && isset($_POST['delete_id'])) {
    verify_csrf();
    $id = (int) $_POST['delete_id'];
    $response = $api->delete('/directory/'.$id);
    if ($response['status'] === 200) {
        flash('success', 'Kayıt silindi.');
    } else {
        flash('error', 'Silme başarısız.');
    }
    redirect('directory.php'.($search !== '' ? '?search='.urlencode($search) : ''));
}

$query = $search !== '' ? ['search' => $search] : [];
$response = $api->get('/directory', $query);
$contacts = [];
if ($response['status'] === 200 && is_array($response['body']['data'] ?? null)) {
    $contacts = $response['body']['data'];
}

$activeNav = 'directory';
$pageTitle = 'Rehber — '.app_name($config);

ob_start();
?>
<h1>Rehber</h1>

<form method="get" class="actions" style="margin-top:0">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Ara..." style="max-width:280px">
    <button type="submit" class="btn secondary">Ara</button>
    <?php if ($canEdit): ?>
    <a href="directory-edit.php" class="btn">Yeni kişi</a>
    <?php endif; ?>
</form>

<?php if ($response['status'] !== 200): ?>
<div class="alert err">Rehber yüklenemedi (HTTP <?= (int) $response['status'] ?>).</div>
<?php elseif ($contacts === []): ?>
<p class="meta">Kayıt bulunamadı.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>Ad Soyad</th>
            <th>Telefon</th>
            <th>Açıklama</th>
            <?php if ($canEdit): ?><th></th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($contacts as $row): ?>
        <tr>
            <td><?= e($row['full_name'] ?? '') ?></td>
            <td><?= e($row['phone'] ?? '') ?></td>
            <td><?= e($row['description'] ?? '') ?></td>
            <?php if ($canEdit): ?>
            <td style="white-space:nowrap">
                <a href="directory-edit.php?id=<?= (int) ($row['id'] ?? 0) ?>">Düzenle</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Silinsin mi?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="delete_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                    <button type="submit" class="btn danger" style="padding:.2rem .5rem;font-size:.8rem">Sil</button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<div class="actions">
    <a href="dashboard.php" class="btn secondary">Geri</a>
</div>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
