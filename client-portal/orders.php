<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

if (! can('orders')) {
    http_response_code(403);
    exit('Sipariş görüntüleme izniniz yok.');
}

$search = trim((string) ($_GET['search'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$query = ['page' => $page];
if ($search !== '') {
    $query['search'] = $search;
}

$response = $api->get('/orders', $query);
$orders = ($response['status'] === 200 && is_array($response['body']['data'] ?? null)) ? $response['body']['data'] : [];
$meta = is_array($response['body']['meta'] ?? null) ? $response['body']['meta'] : [];

$activeNav = 'orders';
$pageTitle = 'Siparişler — '.app_name($config);

ob_start();
?>
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col"><h2 class="page-title mb-0">Siparişler</h2></div>
        <?php if (can('edit_orders')): ?>
        <div class="col-auto"><a href="order-form.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Yeni sipariş</a></div>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3"><div class="card-body py-3">
    <form method="get" class="row g-2">
        <div class="col-md-10"><input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Sipariş no ara..."></div>
        <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100">Filtrele</button></div>
    </form>
</div></div>

<?php if ($response['status'] !== 200): ?>
<div class="alert alert-danger"><?= e(api_error_message($response, 'Siparişler yüklenemedi')) ?></div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table mb-0">
            <thead><tr><th>No</th><th>Tarih</th><th>Teslimat</th><th>Tutar</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php if ($orders === []): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Kayıt bulunamadı</td></tr>
            <?php else: foreach ($orders as $row): ?>
            <tr>
                <td><a href="order.php?id=<?= urlencode($row['id'] ?? '') ?>" class="fw-semibold"><?= e($row['order_number'] ?? '') ?></a></td>
                <td><?= fmt_date($row['order_date'] ?? null) ?></td>
                <td><?= fmt_date($row['delivery_date'] ?? null) ?></td>
                <td><?= fmt_money($row['total_amount'] ?? 0, $row['currency'] ?? 'TRY') ?></td>
                <td><?= status_badge($row['status'] ?? '', 'order') ?></td>
                <td class="text-end"><a href="order.php?id=<?= urlencode($row['id'] ?? '') ?>" class="btn btn-sm btn-ghost-primary">Detay</a></td>
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
