<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

if (! can('shipments')) {
    http_response_code(403);
    exit('Sevkiyat görüntüleme izniniz yok.');
}

$id = trim((string) ($_GET['id'] ?? ''));
if ($id === '') {
    redirect('shipments.php');
}

$showCosts = can('shipment_costs');
$canEditCosts = can('edit_shipment_costs');
$canEditShipment = can('edit_shipments');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cost']) && $canEditCosts) {
    verify_csrf();
    $api->delete('/shipment-costs/'.trim((string) $_POST['delete_cost']));
    flash('success', 'Masraf silindi.');
    redirect('shipment.php?id='.urlencode($id));
}

$response = $api->get('/shipments/'.$id);
if ($response['status'] !== 200 || ! is_array($response['body']['data'] ?? null)) {
    flash('error', 'Sevkiyat bulunamadı.');
    redirect('shipments.php');
}

$shipment = $response['body']['data'];
$costs = $showCosts ? ($shipment['costs'] ?? []) : [];

$activeNav = 'shipments';
$pageTitle = ($shipment['shipment_number'] ?? 'Sevkiyat').' — '.app_name($config);

ob_start();
?>
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle"><a href="shipments.php" class="text-muted">Sevkiyatlar</a></div>
            <h2 class="page-title mb-0"><?= e($shipment['shipment_number'] ?? '') ?></h2>
        </div>
        <div class="col-auto d-flex gap-2">
            <?php if ($canEditShipment): ?><a href="shipment-form.php?id=<?= urlencode($id) ?>" class="btn btn-primary"><i class="ti ti-edit me-1"></i>Düzenle</a><?php endif; ?>
            <a href="shipments.php" class="btn btn-outline-secondary">Liste</a>
        </div>
    </div>
</div>

<div class="card mb-3"><div class="card-body">
    <dl class="row detail-grid mb-0">
        <dt class="col-sm-3">Sipariş</dt><dd class="col-sm-9"><?php if (! empty($shipment['order_id'])): ?><a href="order.php?id=<?= urlencode($shipment['order_id']) ?>"><?= e($shipment['order_number'] ?? $shipment['order_id']) ?></a><?php else: ?>—<?php endif; ?></dd>
        <dt class="col-sm-3">Taşıma modu</dt><dd class="col-sm-9"><?= e(transport_label($shipment['transport_mode'] ?? '')) ?></dd>
        <dt class="col-sm-3">Çıkış</dt><dd class="col-sm-9"><?= e($shipment['origin'] ?? '—') ?></dd>
        <dt class="col-sm-3">Varış</dt><dd class="col-sm-9"><?= e($shipment['destination'] ?? '—') ?></dd>
        <dt class="col-sm-3">ETD / ETA</dt><dd class="col-sm-9"><?= fmt_date($shipment['etd'] ?? null) ?> / <?= fmt_date($shipment['eta'] ?? null) ?></dd>
        <dt class="col-sm-3">ATD / ATA</dt><dd class="col-sm-9"><?= fmt_date($shipment['atd'] ?? null, true) ?> / <?= fmt_date($shipment['ata'] ?? null, true) ?></dd>
        <dt class="col-sm-3">Durum</dt><dd class="col-sm-9"><?= status_badge($shipment['status'] ?? '', 'shipment') ?></dd>
        <?php if ($showCosts): ?>
        <dt class="col-sm-3">Toplam masraf</dt><dd class="col-sm-9 fw-bold"><?= fmt_money($shipment['total_cost'] ?? 0, $shipment['currency'] ?? 'USD') ?></dd>
        <?php endif; ?>
        <?php if (! empty($shipment['cargo_description'])): ?>
        <dt class="col-sm-3">Yük</dt><dd class="col-sm-9"><?= e($shipment['cargo_description']) ?></dd>
        <?php endif; ?>
        <?php if (! empty($shipment['notes'])): ?>
        <dt class="col-sm-3">Not</dt><dd class="col-sm-9"><?= nl2br(e($shipment['notes'])) ?></dd>
        <?php endif; ?>
    </dl>
</div></div>

<?php if ($showCosts): ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Masraflar</h3>
        <?php if ($canEditCosts): ?>
        <a href="cost-edit.php?shipment_id=<?= urlencode($id) ?>" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>Yeni masraf</a>
        <?php elseif ($showCosts): ?>
        <span class="text-muted small">Masraf ekleme izni kapalı</span>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table mb-0">
            <thead><tr><th>Kalem</th><th>Tarih</th><th>Tutar</th><th>Durum</th><?php if ($canEditCosts): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
            <?php if ($costs === []): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Masraf kaydı yok</td></tr>
            <?php else: foreach ($costs as $cost): ?>
            <tr>
                <td><?= e($cost['item_name'] ?? $cost['description'] ?? '—') ?></td>
                <td><?= fmt_date($cost['expense_date'] ?? null) ?></td>
                <td><?= fmt_money($cost['amount'] ?? 0, $cost['currency'] ?? 'USD') ?></td>
                <td><?= e(cost_status_label($cost['status'] ?? '')) ?></td>
                <?php if ($canEditCosts): ?>
                <td class="text-end text-nowrap">
                    <a href="cost-edit.php?shipment_id=<?= urlencode($id) ?>&id=<?= urlencode($cost['id'] ?? '') ?>" class="btn btn-sm btn-ghost-primary">Düzenle</a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Silinsin mi?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_cost" value="<?= e($cost['id'] ?? '') ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
