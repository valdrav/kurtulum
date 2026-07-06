<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

if (! can('edit_shipment_costs') || ! can('shipment_costs')) {
    http_response_code(403);
    exit('Masraf düzenleme izniniz yok.');
}

$shipmentId = trim((string) ($_GET['shipment_id'] ?? ''));
$costId = trim((string) ($_GET['id'] ?? ''));
$isEdit = $costId !== '';

if ($shipmentId === '') {
    redirect('shipments.php');
}

$cost = [
    'item_name' => '',
    'description' => '',
    'amount' => '',
    'currency' => 'USD',
    'expense_date' => date('Y-m-d'),
    'status' => 'pending',
    'type' => '',
    'notes' => '',
];

if ($isEdit) {
    $shipResp = $api->get('/shipments/'.$shipmentId);
    if ($shipResp['status'] !== 200) {
        flash('error', 'Sevkiyat bulunamadı.');
        redirect('shipments.php');
    }
    $found = null;
    foreach ($shipResp['body']['data']['costs'] ?? [] as $c) {
        if (($c['id'] ?? '') === $costId) {
            $found = $c;
            break;
        }
    }
    if (! $found) {
        flash('error', 'Masraf bulunamadı.');
        redirect('shipment.php?id='.urlencode($shipmentId));
    }
    $cost = array_merge($cost, $found);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $payload = [
        'item_name' => trim((string) ($_POST['item_name'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'amount' => (float) ($_POST['amount'] ?? 0),
        'currency' => strtoupper(trim((string) ($_POST['currency'] ?? 'USD'))),
        'expense_date' => trim((string) ($_POST['expense_date'] ?? '')),
        'status' => trim((string) ($_POST['status'] ?? 'pending')),
        'type' => 'expense',
        'notes' => trim((string) ($_POST['notes'] ?? '')),
    ];

    if ($payload['item_name'] === '') {
        $formError = 'Kalem adı zorunludur.';
    } elseif ($payload['amount'] <= 0) {
        $formError = 'Tutar sıfırdan büyük olmalıdır.';
    } elseif (strlen($payload['currency']) !== 3) {
        $formError = 'Para birimi 3 harf olmalıdır (ör. USD, TRY).';
    } else {
        if ($payload['expense_date'] === '') {
            unset($payload['expense_date']);
        }
        if ($payload['description'] === '') {
            unset($payload['description']);
        }
        if ($payload['notes'] === '') {
            unset($payload['notes']);
        }

        $response = $isEdit
            ? $api->patch('/shipment-costs/'.$costId, $payload)
            : $api->post('/shipments/'.$shipmentId.'/costs', $payload);

        if (in_array($response['status'], [200, 201], true)) {
            flash('success', $isEdit ? 'Masraf güncellendi.' : 'Masraf eklendi.');
            redirect('shipment.php?id='.urlencode($shipmentId));
        }
        $formError = api_error_message($response, 'Kayıt başarısız');
    }
}

$formError = $formError ?? null;

$activeNav = 'shipments';
$pageTitle = ($isEdit ? 'Masraf düzenle' : 'Yeni masraf').' — '.app_name($config);

ob_start();
?>
<?= page_actions($isEdit ? 'Masraf düzenle' : 'Yeni masraf') ?>

<?php if ($formError): ?><div class="alert alert-danger"><?= e($formError) ?></div><?php endif; ?>

<?php if (! can('edit_shipment_costs')): ?>
<div class="alert alert-warning">Masraf ekleme izniniz kapalı. Ana sistem → Ayarlar → Harici API → "Sevkiyat masrafı düzenleme" işaretleyip kaydedin.</div>
<?php else: ?>
<form method="post">
    <?= csrf_field() ?>
    <div class="card mb-3"><div class="card-body">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Kalem *</label><input name="item_name" class="form-control" value="<?= e($cost['item_name'] ?? '') ?>" required></div>
            <div class="col-md-3"><label class="form-label">Tutar *</label><input name="amount" type="number" step="0.01" min="0" class="form-control" value="<?= e((string) ($cost['amount'] ?? '')) ?>" required></div>
            <div class="col-md-3"><label class="form-label">Para birimi</label><input name="currency" maxlength="3" class="form-control" value="<?= e($cost['currency'] ?? 'USD') ?>"></div>
            <div class="col-md-4"><label class="form-label">Tarih</label><input name="expense_date" type="date" class="form-control" value="<?= e($cost['expense_date'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Durum</label>
                <select name="status" class="form-select">
                    <?php foreach (['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'delivered' => 'Teslim'] as $k => $label): ?>
                    <option value="<?= $k ?>" <?= ($cost['status'] ?? '') === $k ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12"><label class="form-label">Açıklama</label><textarea name="description" class="form-control" rows="2"><?= e($cost['description'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label">Not</label><textarea name="notes" class="form-control" rows="2"><?= e($cost['notes'] ?? '') ?></textarea></div>
        </div>
    </div></div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Kaydet</button>
        <a href="shipment.php?id=<?= urlencode($shipmentId) ?>" class="btn btn-outline-secondary">İptal</a>
    </div>
</form>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
