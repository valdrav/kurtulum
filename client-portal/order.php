<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

if (! can('orders')) {
    http_response_code(403);
    exit('Sipariş görüntüleme izniniz yok.');
}

$id = trim((string) ($_GET['id'] ?? ''));
if ($id === '') {
    redirect('orders.php');
}

$response = $api->get('/orders/'.$id);
if ($response['status'] !== 200 || ! is_array($response['body']['data'] ?? null)) {
    flash('error', 'Sipariş bulunamadı.');
    redirect('orders.php');
}

$order = $response['body']['data'];
$activeNav = 'orders';
$pageTitle = ($order['order_number'] ?? 'Sipariş').' — '.app_name($config);

ob_start();
?>
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle"><a href="orders.php" class="text-muted">Siparişler</a></div>
            <h2 class="page-title mb-0"><?= e($order['order_number'] ?? '') ?></h2>
        </div>
        <div class="col-auto d-flex gap-2">
            <?php if (can('edit_orders')): ?><a href="order-form.php?id=<?= urlencode($id) ?>" class="btn btn-primary"><i class="ti ti-edit me-1"></i>Düzenle</a><?php endif; ?>
            <?php if (can('edit_shipments')): ?><a href="shipment-form.php?order_id=<?= urlencode($id) ?>" class="btn btn-outline-primary"><i class="ti ti-truck me-1"></i>Sevkiyat ekle</a><?php endif; ?>
            <a href="orders.php" class="btn btn-outline-secondary">Liste</a>
        </div>
    </div>
</div>

<div class="card mb-3"><div class="card-body">
    <dl class="row detail-grid mb-0">
        <dt class="col-sm-3">Sipariş tarihi</dt><dd class="col-sm-9"><?= fmt_date($order['order_date'] ?? null) ?></dd>
        <dt class="col-sm-3">Teslimat tarihi</dt><dd class="col-sm-9"><?= fmt_date($order['delivery_date'] ?? null) ?></dd>
        <dt class="col-sm-3">Durum</dt><dd class="col-sm-9"><?= status_badge($order['status'] ?? '', 'order') ?></dd>
        <dt class="col-sm-3">Incoterm</dt><dd class="col-sm-9"><?= e($order['incoterm'] ?? '—') ?></dd>
        <dt class="col-sm-3">Toplam</dt><dd class="col-sm-9 fw-bold"><?= fmt_money($order['total_amount'] ?? 0, $order['currency'] ?? 'TRY') ?></dd>
        <?php if (! empty($order['shipping_address']) || ! empty($order['shipping_city'])): ?>
        <dt class="col-sm-3">Teslimat</dt><dd class="col-sm-9"><?= e(trim(($order['shipping_address'] ?? '').', '.($order['shipping_city'] ?? '').' '.($order['shipping_country'] ?? ''), ', ')) ?></dd>
        <?php endif; ?>
        <?php if (! empty($order['notes'])): ?>
        <dt class="col-sm-3">Not</dt><dd class="col-sm-9"><?= nl2br(e($order['notes'])) ?></dd>
        <?php endif; ?>
    </dl>
</div></div>

<?php if (! empty($order['items'])): ?>
<div class="card">
    <div class="card-header"><h3 class="card-title mb-0">Kalemler</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table mb-0">
            <thead><tr><th>Ürün</th><th>Miktar</th><th>Birim fiyat</th><th>Toplam</th></tr></thead>
            <tbody>
            <?php foreach ($order['items'] as $item): ?>
            <tr>
                <td><?= e($item['product_name'] ?? $item['description'] ?? '—') ?></td>
                <td><?= e((string) ($item['quantity'] ?? '')) ?> <?= e($item['unit'] ?? '') ?></td>
                <td><?= fmt_money($item['unit_price'] ?? 0, $order['currency'] ?? 'TRY') ?></td>
                <td><?= fmt_money($item['total'] ?? 0, $order['currency'] ?? 'TRY') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
