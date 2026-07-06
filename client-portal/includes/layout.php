<?php

declare(strict_types=1);

/** @var array $config */
/** @var ApiClient $api */

$pageTitle = $pageTitle ?? app_name($config);
$activeNav = $activeNav ?? '';
$user = Auth::user();
$me = $_SESSION['api_me'] ?? null;
$companyName = is_array($me) ? ($me['customer']['company_name'] ?? '') : '';

$navItems = [];
if ($user) {
    $navItems[] = ['id' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'ti-dashboard', 'label' => 'Panel'];
    if (can('customer')) {
        $navItems[] = ['id' => 'customer', 'href' => 'customer.php', 'icon' => 'ti-building', 'label' => 'Müşteri'];
    }
    if (can('orders')) {
        $navItems[] = ['id' => 'orders', 'href' => 'orders.php', 'icon' => 'ti-shopping-cart', 'label' => 'Siparişler'];
    }
    if (can('shipments')) {
        $navItems[] = ['id' => 'shipments', 'href' => 'shipments.php', 'icon' => 'ti-truck-delivery', 'label' => 'Sevkiyatlar'];
    }
    if (can('directory')) {
        $navItems[] = ['id' => 'directory', 'href' => 'directory.php', 'icon' => 'ti-address-book', 'label' => 'Rehber'];
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.3.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link href="assets/style.css?v=2" rel="stylesheet">
</head>
<body class="ef-app">
<?php if ($user): ?>
<div class="ef-portal-shell">
    <aside class="ef-portal-sidebar">
        <div class="brand-block">
            <div class="company"><?= e($companyName ?: app_name($config)) ?></div>
            <div class="subtitle"><?= e(app_name($config)) ?></div>
        </div>
        <nav class="ef-sidebar-nav">
            <?php foreach ($navItems as $item): ?>
            <a class="ef-sidebar-link <?= $activeNav === $item['id'] ? 'active' : '' ?>" href="<?= e($item['href']) ?>">
                <i class="ti <?= e($item['icon']) ?>"></i>
                <span><?= e($item['label']) ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="ef-portal-main">
        <header class="ef-portal-topbar">
            <div class="text-truncate">
                <span class="fw-semibold d-lg-none"><?= e(app_name($config)) ?></span>
                <span class="text-muted small d-none d-lg-inline"><?= e($companyName) ?></span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span class="text-muted small d-none d-md-inline"><?= e($user) ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-secondary"><i class="ti ti-logout"></i><span class="d-none d-sm-inline ms-1">Çıkış</span></a>
            </div>
        </header>
        <nav class="ef-portal-mobile-nav" aria-label="Mobil menü">
            <?php foreach ($navItems as $item): ?>
            <a class="btn btn-sm <?= $activeNav === $item['id'] ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <main class="ef-portal-content">
            <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
            <?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>
            <?= $content ?? '' ?>
        </main>
    </div>
</div>
<?php else: ?>
<main>
    <?php if ($msg = flash('success')): ?><div class="alert alert-success m-3"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash('error')): ?><div class="alert alert-danger m-3"><?= e($msg) ?></div><?php endif; ?>
    <?= $content ?? '' ?>
</main>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
<?= $pageScripts ?? '' ?>
</body>
</html>
