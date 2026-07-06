<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

if (! can('shipments')) {
    http_response_code(403);
    exit('Sevkiyat görüntüleme izniniz yok.');
}

$search = trim((string) ($_GET['search'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$query = ['page' => $page];
if ($search !== '') {
    $query['search'] = $search;
}

$response = $api->get('/shipments', $query);
$shipments = ($response['status'] === 200 && is_array($response['body']['data'] ?? null)) ? $response['body']['data'] : [];
$meta = is_array($response['body']['meta'] ?? null) ? $response['body']['meta'] : [];

$activeNav = 'shipments';
$pageTitle = 'Sevkiyatlar — '.app_name($config);

ob_start();
?>
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col"><h2 class="page-title mb-0">Sevkiyatlar</h2></div>
        <?php if (can('edit_shipments')): ?>
        <div class="col-auto"><a href="shipment-form.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Yeni sevkiyat</a></div>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3"><div class="card-body py-3">
    <form method="get" class="row g-2">
        <div class="col-md-10"><input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Sevkiyat no ara..."></div>
        <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100">Filtrele</button></div>
    </form>
</div></div>

<?php if ($response['status'] !== 200): ?>
<div class="alert alert-danger"><?= e(api_error_message($response, 'Sevkiyatlar yüklenemedi')) ?></div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table mb-0">
            <thead><tr><th>No</th><th>Sipariş</th><th>Mod</th><th>Çıkış → Varış</th><th>Durum</th><?php if (can('shipment_costs')): ?><th>Masraf</th><?php endif; ?><th></th></tr></thead>
            <tbody>
            <?php if ($shipments === []): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Kayıt bulunamadı</td></tr>
            <?php else: foreach ($shipments as $row): ?>
            <tr>
                <td><a href="shipment.php?id=<?= urlencode($row['id'] ?? '') ?>" class="fw-semibold"><?= e($row['shipment_number'] ?? '') ?></a></td>
                <td><?= e($row['order_number'] ?? '—') ?></td>
                <td><?= e(transport_label($row['transport_mode'] ?? '')) ?></td>
                <td><?= e($row['origin'] ?? '—') ?> → <?= e($row['destination'] ?? '—') ?></td>
                <td><?= status_badge($row['status'] ?? '', 'shipment') ?></td>
                <?php if (can('shipment_costs')): ?><td><?= isset($row['total_cost']) ? fmt_money($row['total_cost'], $row['currency'] ?? 'USD') : '—' ?></td><?php endif; ?>
                <td class="text-end"><a href="shipment.php?id=<?= urlencode($row['id'] ?? '') ?>" class="btn btn-sm btn-ghost-primary">Detay</a></td>
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
