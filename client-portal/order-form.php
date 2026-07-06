<?php

require __DIR__.'/includes/bootstrap.php';
require_login();

if (! can('edit_orders')) {
    http_response_code(403);
    exit('Sipariş düzenleme izniniz yok.');
}

$id = trim((string) ($_GET['id'] ?? ''));
$isEdit = $id !== '';

$order = [
    'order_number' => '',
    'status' => 'draft',
    'currency' => 'USD',
    'order_date' => date('Y-m-d'),
    'delivery_date' => '',
    'incoterm' => '',
    'notes' => '',
    'shipping_address' => '',
    'shipping_city' => '',
    'shipping_country' => '',
    'items' => [['description' => '', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 0]],
];

if ($isEdit) {
    $resp = $api->get('/orders/'.$id);
    if ($resp['status'] !== 200) {
        flash('error', 'Sipariş bulunamadı.');
        redirect('orders.php');
    }
    $order = array_merge($order, $resp['body']['data']);
    if (empty($order['items'])) {
        $order['items'] = [['description' => '', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 0]];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $items = [];
    $descriptions = $_POST['item_description'] ?? [];
    $quantities = $_POST['item_quantity'] ?? [];
    $units = $_POST['item_unit'] ?? [];
    $prices = $_POST['item_unit_price'] ?? [];
    foreach ($descriptions as $i => $desc) {
        $desc = trim((string) $desc);
        if ($desc === '') {
            continue;
        }
        $items[] = [
            'description' => $desc,
            'quantity' => (float) ($quantities[$i] ?? 0),
            'unit' => trim((string) ($units[$i] ?? 'pcs')),
            'unit_price' => (float) ($prices[$i] ?? 0),
        ];
    }

    $payload = [
        'order_number' => trim((string) ($_POST['order_number'] ?? '')),
        'status' => trim((string) ($_POST['status'] ?? 'draft')),
        'currency' => strtoupper(trim((string) ($_POST['currency'] ?? 'USD'))),
        'order_date' => trim((string) ($_POST['order_date'] ?? '')),
        'delivery_date' => trim((string) ($_POST['delivery_date'] ?? '')),
        'incoterm' => trim((string) ($_POST['incoterm'] ?? '')),
        'notes' => trim((string) ($_POST['notes'] ?? '')),
        'shipping_address' => trim((string) ($_POST['shipping_address'] ?? '')),
        'shipping_city' => trim((string) ($_POST['shipping_city'] ?? '')),
        'shipping_country' => trim((string) ($_POST['shipping_country'] ?? '')),
        'items' => $items,
    ];

    $response = $isEdit
        ? $api->patch('/orders/'.$id, $payload)
        : $api->post('/orders', $payload);

    if (in_array($response['status'], [200, 201], true)) {
        $newId = $response['body']['data']['id'] ?? $id;
        flash('success', $isEdit ? 'Sipariş güncellendi.' : 'Sipariş oluşturuldu.');
        redirect('order.php?id='.urlencode($newId));
    }
    flash('error', api_error_message($response, 'Kayıt başarısız'));
}

$activeNav = 'orders';
$pageTitle = ($isEdit ? 'Sipariş düzenle' : 'Yeni sipariş').' — '.app_name($config);
$statuses = ['draft', 'confirmed', 'production', 'ready', 'shipped', 'delivered'];

ob_start();
?>
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col"><h2 class="page-title mb-0"><?= $isEdit ? 'Sipariş düzenle' : 'Yeni sipariş' ?></h2></div>
        <div class="col-auto"><a href="<?= $isEdit ? 'order.php?id='.urlencode($id) : 'orders.php' ?>" class="btn btn-outline-secondary">İptal</a></div>
    </div>
</div>

<form method="post">
    <?= csrf_field() ?>
    <div class="card mb-3"><div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Sipariş no</label>
                <input type="text" name="order_number" class="form-control" value="<?= e($order['order_number'] ?? '') ?>" placeholder="Boş bırakılırsa otomatik">
            </div>
            <div class="col-md-4">
                <label class="form-label">Durum</label>
                <select name="status" class="form-select">
                    <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= ($order['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(order_status_label($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Para birimi</label>
                <input type="text" name="currency" class="form-control" maxlength="3" value="<?= e($order['currency'] ?? 'USD') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Sipariş tarihi</label>
                <input type="date" name="order_date" class="form-control" value="<?= e($order['order_date'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Teslimat tarihi</label>
                <input type="date" name="delivery_date" class="form-control" value="<?= e($order['delivery_date'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Incoterm</label>
                <input type="text" name="incoterm" class="form-control" value="<?= e($order['incoterm'] ?? '') ?>">
            </div>
        </div>
    </div></div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Kalemler</h3>
            <button type="button" class="btn btn-sm btn-outline-primary" id="add-item"><i class="ti ti-plus"></i> Kalem ekle</button>
        </div>
        <div class="card-body" id="items-wrap">
            <?php foreach ($order['items'] as $idx => $item): ?>
            <div class="item-row">
                <div class="row g-2">
                    <div class="col-md-5"><label class="form-label">Açıklama *</label><input type="text" name="item_description[]" class="form-control" value="<?= e($item['description'] ?? '') ?>" required></div>
                    <div class="col-md-2"><label class="form-label">Miktar</label><input type="number" step="0.001" min="0" name="item_quantity[]" class="form-control" value="<?= e((string) ($item['quantity'] ?? 1)) ?>"></div>
                    <div class="col-md-2"><label class="form-label">Birim</label><input type="text" name="item_unit[]" class="form-control" value="<?= e($item['unit'] ?? 'pcs') ?>"></div>
                    <div class="col-md-2"><label class="form-label">Birim fiyat</label><input type="number" step="0.01" min="0" name="item_unit_price[]" class="form-control" value="<?= e((string) ($item['unit_price'] ?? 0)) ?>"></div>
                    <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm remove-item w-100" title="Sil"><i class="ti ti-trash"></i></button></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <div class="row g-3">
            <div class="col-12"><label class="form-label">Teslimat adresi</label><textarea name="shipping_address" class="form-control" rows="2"><?= e($order['shipping_address'] ?? '') ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Şehir</label><input type="text" name="shipping_city" class="form-control" value="<?= e($order['shipping_city'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Ülke</label><input type="text" name="shipping_country" class="form-control" value="<?= e($order['shipping_country'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label">Not</label><textarea name="notes" class="form-control" rows="3"><?= e($order['notes'] ?? '') ?></textarea></div>
        </div>
    </div></div>

    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Kaydet</button>
</form>

<?php
$pageScripts = <<<'HTML'
<script>
const wrap = document.getElementById('items-wrap');
document.getElementById('add-item')?.addEventListener('click', () => {
  const row = document.createElement('div');
  row.className = 'item-row';
  row.innerHTML = `<div class="row g-2">
    <div class="col-md-5"><label class="form-label">Açıklama *</label><input type="text" name="item_description[]" class="form-control" required></div>
    <div class="col-md-2"><label class="form-label">Miktar</label><input type="number" step="0.001" min="0" name="item_quantity[]" class="form-control" value="1"></div>
    <div class="col-md-2"><label class="form-label">Birim</label><input type="text" name="item_unit[]" class="form-control" value="pcs"></div>
    <div class="col-md-2"><label class="form-label">Birim fiyat</label><input type="number" step="0.01" min="0" name="item_unit_price[]" class="form-control" value="0"></div>
    <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm remove-item w-100"><i class="ti ti-trash"></i></button></div>
  </div>`;
  wrap.appendChild(row);
});
wrap?.addEventListener('click', e => {
  if (e.target.closest('.remove-item') && wrap.querySelectorAll('.item-row').length > 1) {
    e.target.closest('.item-row')?.remove();
  }
});
</script>
HTML;
$content = ob_get_clean();
require __DIR__.'/includes/layout.php';
