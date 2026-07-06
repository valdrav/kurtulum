<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

if (! can('edit_shipments')) {
    http_response_code(403);
    exit('Sevkiyat düzenleme izniniz yok.');
}

$id = trim((string) ($_GET['id'] ?? ''));
$isEdit = $id !== '';

$shipment = [
    'transport_mode' => 'road',
    'status' => 'draft',
    'currency' => 'USD',
    'origin' => '',
    'destination' => '',
    'cargo_description' => '',
    'notes' => '',
    'order_id' => trim((string) ($_GET['order_id'] ?? '')),
    'incoterm' => '',
    'etd' => '',
    'eta' => '',
    'bl_number' => '',
    'awb_number' => '',
];

if ($isEdit) {
    $resp = $api->get('/shipments/'.$id);
    if ($resp['status'] !== 200) {
        flash('error', 'Sevkiyat bulunamadı.');
        redirect('shipments.php');
    }
    $shipment = array_merge($shipment, $resp['body']['data']);
}

$ordersResp = $api->get('/orders', ['per_page' => 100]);
$orderOptions = ($ordersResp['status'] === 200) ? ($ordersResp['body']['data'] ?? []) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $payload = [
        'order_id' => trim((string) ($_POST['order_id'] ?? '')) ?: null,
        'shipment_number' => trim((string) ($_POST['shipment_number'] ?? '')),
        'transport_mode' => trim((string) ($_POST['transport_mode'] ?? 'road')),
        'status' => trim((string) ($_POST['status'] ?? 'draft')),
        'currency' => strtoupper(trim((string) ($_POST['currency'] ?? 'USD'))),
        'origin' => trim((string) ($_POST['origin'] ?? '')),
        'destination' => trim((string) ($_POST['destination'] ?? '')),
        'cargo_description' => trim((string) ($_POST['cargo_description'] ?? '')),
        'notes' => trim((string) ($_POST['notes'] ?? '')),
        'incoterm' => trim((string) ($_POST['incoterm'] ?? '')),
        'etd' => trim((string) ($_POST['etd'] ?? '')),
        'eta' => trim((string) ($_POST['eta'] ?? '')),
        'bl_number' => trim((string) ($_POST['bl_number'] ?? '')),
        'awb_number' => trim((string) ($_POST['awb_number'] ?? '')),
    ];

    $response = $isEdit
        ? $api->patch('/shipments/'.$id, $payload)
        : $api->post('/shipments', $payload);

    if (in_array($response['status'], [200, 201], true)) {
        $newId = $response['body']['data']['id'] ?? $id;
        flash('success', $isEdit ? 'Sevkiyat güncellendi.' : 'Sevkiyat oluşturuldu.');
        redirect('shipment.php?id='.urlencode($newId));
    }
    flash('error', api_error_message($response, 'Kayıt başarısız'));
}

$activeNav = 'shipments';
$pageTitle = ($isEdit ? 'Sevkiyat düzenle' : 'Yeni sevkiyat').' — '.app_name($config);
$modes = ['road' => 'Kara', 'sea' => 'Deniz', 'air' => 'Hava', 'rail' => 'Demiryolu', 'multimodal' => 'Multimodal'];
$statuses = ['draft', 'booked', 'in_transit', 'at_port', 'customs', 'delivered', 'cancelled'];

ob_start();
?>
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col"><h2 class="page-title mb-0"><?= $isEdit ? 'Sevkiyat düzenle' : 'Yeni sevkiyat' ?></h2></div>
        <div class="col-auto"><a href="<?= $isEdit ? 'shipment.php?id='.urlencode($id) : 'shipments.php' ?>" class="btn btn-outline-secondary">İptal</a></div>
    </div>
</div>

<form method="post">
    <?= csrf_field() ?>
    <div class="card mb-3"><div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Sevkiyat no</label>
                <input type="text" name="shipment_number" class="form-control" value="<?= e($shipment['shipment_number'] ?? '') ?>" placeholder="Boş bırakılırsa otomatik">
            </div>
            <div class="col-md-4">
                <label class="form-label">Bağlı sipariş</label>
                <select name="order_id" class="form-select">
                    <option value="">— Seçilmedi —</option>
                    <?php foreach ($orderOptions as $o): ?>
                    <option value="<?= e($o['id'] ?? '') ?>" <?= ($shipment['order_id'] ?? '') === ($o['id'] ?? '') ? 'selected' : '' ?>><?= e($o['order_number'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Taşıma modu *</label>
                <select name="transport_mode" class="form-select" required>
                    <?php foreach ($modes as $k => $label): ?>
                    <option value="<?= $k ?>" <?= ($shipment['transport_mode'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Durum</label>
                <select name="status" class="form-select">
                    <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= ($shipment['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(shipment_status_label($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Para birimi</label>
                <input type="text" name="currency" class="form-control" maxlength="3" value="<?= e($shipment['currency'] ?? 'USD') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Incoterm</label>
                <input type="text" name="incoterm" class="form-control" value="<?= e($shipment['incoterm'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Çıkış</label>
                <input type="text" name="origin" class="form-control" value="<?= e($shipment['origin'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Varış</label>
                <input type="text" name="destination" class="form-control" value="<?= e($shipment['destination'] ?? '') ?>">
            </div>
            <div class="col-md-3"><label class="form-label">ETD</label><input type="date" name="etd" class="form-control" value="<?= e($shipment['etd'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">ETA</label><input type="date" name="eta" class="form-control" value="<?= e($shipment['eta'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">BL No</label><input type="text" name="bl_number" class="form-control" value="<?= e($shipment['bl_number'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">AWB No</label><input type="text" name="awb_number" class="form-control" value="<?= e($shipment['awb_number'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label">Yük açıklaması</label><textarea name="cargo_description" class="form-control" rows="2"><?= e($shipment['cargo_description'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label">Not</label><textarea name="notes" class="form-control" rows="2"><?= e($shipment['notes'] ?? '') ?></textarea></div>
        </div>
    </div></div>
    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Kaydet</button>
</form>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
