<?php
/**
 * Bağımlılık yok — sadece bu dosyayı yükleyip açın: https://log.kurtulum.com/yukle-kontrol.php
 */
header('Content-Type: text/html; charset=utf-8');
$root = __DIR__;

$required = [
    'index.php',
    'login.php',
    'check.php',
    'dashboard.php',
    'customer.php',
    'directory.php',
    'directory-edit.php',
    'orders.php',
    'order.php',
    'order-form.php',
    'shipments.php',
    'shipment.php',
    'shipment-form.php',
    'cost-edit.php',
    'logout.php',
    'assets/style.css',
    'includes/bootstrap.php',
    'includes/config.php',
    'includes/Auth.php',
    'includes/ApiClient.php',
    'includes/helpers.php',
    'includes/layout.php',
];

$nested = 'client-portal/includes/bootstrap.php';
$hasNested = is_file($root.'/'.$nested);

?><!DOCTYPE html>
<html lang="tr"><head><meta charset="utf-8"><title>Yükleme kontrolü</title>
<style>body{font-family:sans-serif;max-width:640px;margin:2rem auto;padding:0 1rem}
.ok{color:green}.err{color:#c00}.box{background:#f5f5f5;padding:1rem;border-radius:8px;margin:1rem 0}
code{background:#eee;padding:2px 6px}</style></head><body>
<h1>log.kurtulum.com — dosya kontrolü</h1>
<p>Kök klasör: <code><?= htmlspecialchars($root) ?></code></p>

<?php if ($hasNested): ?>
<div class="box err">
    <strong>Yanlış klasör yapısı!</strong> Dosyalar <code>client-portal/</code> altında kalmış.
    Plesk File Manager ile <code>client-portal</code> <em>içindekileri</em> site köküne taşıyın
    (iç içe client-portal/client-portal olmasın).
</div>
<?php endif; ?>

<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%">
<tr><th>Dosya</th><th>Durum</th></tr>
<?php
$missing = 0;
foreach ($required as $f) {
    $ok = is_file($root.'/'.$f);
    if (! $ok) {
        $missing++;
    }
    echo '<tr><td><code>'.htmlspecialchars($f).'</code></td>';
    echo '<td class="'.($ok ? 'ok">VAR' : 'err">YOK').'</td></tr>';
}
?>
</table>

<?php if ($missing === 0 && ! $hasNested): ?>
<p class="ok"><strong>Tüm dosyalar tamam.</strong> <a href="check.php">check.php</a> → <a href="login.php">login.php</a></p>
<?php else: ?>
<div class="box">
    <strong><?= (int) $missing ?> dosya eksik.</strong>
    <ol>
        <li>Bilgisayarda <code>ticari/client-portal-upload.zip</code> oluşturun (aşağıdaki komut) veya tüm <code>client-portal/</code> klasörünü FTP ile atın.</li>
        <li>Plesk → File Manager → <code>log.kurtulum.com</code> kökü → Upload zip → <strong>Extract / Ayıkla</strong></li>
        <li><code>includes</code> klasörü kökte görünmeli: <code>log.kurtulum.com/includes/bootstrap.php</code></li>
        <li>Bu sayfayı yenileyin.</li>
    </ol>
</div>
<?php endif; ?>
</body></html>
