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
    $response = $api->delete('/directory/'.(int) $_POST['delete_id']);
    flash($response['status'] === 200 ? 'success' : 'error', $response['status'] === 200 ? 'Kayıt silindi.' : 'Silme başarısız.');
    redirect('directory.php'.($search !== '' ? '?search='.urlencode($search) : ''));
}

$query = $search !== '' ? ['search' => $search] : [];
$response = $api->get('/directory', $query);
$contacts = ($response['status'] === 200) ? ($response['body']['data'] ?? []) : [];
$meta = is_array($response['body']['meta'] ?? null) ? $response['body']['meta'] : [];

$activeNav = 'directory';
$pageTitle = 'Rehber — '.app_name($config);

ob_start();
?>
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col"><h2 class="page-title mb-0">Rehber</h2></div>
        <?php if ($canEdit): ?><div class="col-auto"><a href="directory-edit.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Yeni kişi</a></div><?php endif; ?>
    </div>
</div>

<div class="card mb-3"><div class="card-body py-3">
    <form method="get" class="row g-2">
        <div class="col-md-10"><input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Ad, telefon ara..."></div>
        <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100">Filtrele</button></div>
    </form>
</div></div>

<?php if ($response['status'] !== 200): ?>
<div class="alert alert-danger"><?= e(api_error_message($response, 'Rehber yüklenemedi')) ?></div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table mb-0">
            <thead><tr><th>Ad Soyad</th><th>Telefon</th><th>Açıklama</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
            <?php if ($contacts === []): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Kayıt bulunamadı</td></tr>
            <?php else: foreach ($contacts as $row): ?>
            <?php $wa = whatsapp_url($row['phone'] ?? null); ?>
            <tr>
                <td><?= e($row['full_name'] ?? '') ?></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span><?= e($row['phone'] ?? '') ?></span>
                        <?php if ($wa): ?><a href="<?= e($wa) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-success" title="WhatsApp"><i class="ti ti-brand-whatsapp"></i></a><?php endif; ?>
                    </div>
                </td>
                <td class="text-muted"><?= e($row['description'] ?? '—') ?></td>
                <?php if ($canEdit): ?>
                <td class="text-end text-nowrap">
                    <a href="directory-edit.php?id=<?= (int) ($row['id'] ?? 0) ?>" class="btn btn-sm btn-ghost-primary">Düzenle</a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Silinsin mi?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($meta !== []): ?><div class="card-footer"><?= pagination_html($meta, $search !== '' ? ['search' => $search] : []) ?></div><?php endif; ?>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
