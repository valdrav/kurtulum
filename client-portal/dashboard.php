<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

$me = $_SESSION['api_me'] ?? null;
$perms = api_permissions();
$stats = is_array($me) ? ($me['stats'] ?? []) : [];
$recentOrders = [];
$recentShipments = [];

if (can('orders')) {
    $resp = $api->get('/orders', ['per_page' => 5]);
    if ($resp['status'] === 200) {
        $recentOrders = $resp['body']['data'] ?? [];
    }
}
if (can('shipments')) {
    $resp = $api->get('/shipments', ['per_page' => 5]);
    if ($resp['status'] === 200) {
        $recentShipments = $resp['body']['data'] ?? [];
    }
}

$activeNav = 'dashboard';
$pageTitle = 'Panel — '.app_name($config);

ob_start();
?>
<?= page_actions('Panel') ?>

<?php if (! is_array($me)): ?>
<div class="alert alert-danger">API bağlantı bilgisi alınamadı.</div>
<?php else: ?>
<p class="text-muted mb-3">Bağlantı: <strong><?= e($me['connection'] ?? '—') ?></strong> · <?= e($me['customer']['company_name'] ?? '') ?></p>

<div class="row row-cards mb-3">
    <?php if (! empty($perms['orders'])): ?>
    <div class="col-6 col-md-4">
        <div class="card stat-card"><div class="card-body">
            <div class="text-muted small">Siparişler</div>
            <div class="stat-value"><?= (int) ($stats['orders'] ?? 0) ?></div>
        </div></div>
    </div>
    <?php endif; ?>
    <?php if (! empty($perms['shipments'])): ?>
    <div class="col-6 col-md-4">
        <div class="card stat-card"><div class="card-body">
            <div class="text-muted small">Sevkiyatlar</div>
            <div class="stat-value"><?= (int) ($stats['shipments'] ?? 0) ?></div>
        </div></div>
    </div>
    <?php endif; ?>
</div>

<div class="row row-cards">
    <?php if (! empty($perms['orders'])): ?>
    <div class="col-lg-6 mb-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Son siparişler</h3>
                <a href="orders.php" class="btn btn-sm btn-outline-primary">Tümü</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table mb-0">
                    <thead><tr><th>No</th><th>Tarih</th><th>Tutar</th><th>Durum</th></tr></thead>
                    <tbody>
                    <?php if ($recentOrders === []): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Kayıt yok</td></tr>
                    <?php else: foreach ($recentOrders as $row): ?>
                    <tr>
                        <td><a href="order.php?id=<?= urlencode($row['id'] ?? '') ?>" class="fw-semibold"><?= e($row['order_number'] ?? '') ?></a></td>
                        <td><?= fmt_date($row['order_date'] ?? null) ?></td>
                        <td><?= fmt_money($row['total_amount'] ?? 0, $row['currency'] ?? 'TRY') ?></td>
                        <td><?= status_badge($row['status'] ?? '', 'order') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (! empty($perms['shipments'])): ?>
    <div class="col-lg-6 mb-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Son sevkiyatlar</h3>
                <a href="shipments.php" class="btn btn-sm btn-outline-primary">Tümü</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table mb-0">
                    <thead><tr><th>No</th><th>Mod</th><th>Durum</th></tr></thead>
                    <tbody>
                    <?php if ($recentShipments === []): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">Kayıt yok</td></tr>
                    <?php else: foreach ($recentShipments as $row): ?>
                    <tr>
                        <td><a href="shipment.php?id=<?= urlencode($row['id'] ?? '') ?>" class="fw-semibold"><?= e($row['shipment_number'] ?? '') ?></a></td>
                        <td><?= e(transport_label($row['transport_mode'] ?? '')) ?></td>
                        <td><?= status_badge($row['status'] ?? '', 'shipment') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row row-cards mt-1">
    <?php if (! empty($perms['customer'])): ?>
    <div class="col-md-4"><div class="card"><div class="card-body">
        <a href="customer.php" class="stretched-link fw-semibold"><i class="ti ti-building me-1"></i> Müşteri bilgileri</a>
        <div class="text-muted small mt-1"><?= ! empty($perms['edit_customer']) ? 'Görüntüle · düzenle' : 'Salt okunur' ?></div>
    </div></div></div>
    <?php endif; ?>
    <?php if (! empty($perms['directory'])): ?>
    <div class="col-md-4"><div class="card"><div class="card-body">
        <a href="directory.php" class="stretched-link fw-semibold"><i class="ti ti-address-book me-1"></i> Rehber</a>
        <div class="text-muted small mt-1"><?= ! empty($perms['edit_directory']) ? 'Kişi ekle/düzenle' : 'Salt okunur' ?></div>
    </div></div></div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
