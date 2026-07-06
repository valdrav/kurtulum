<?php

declare(strict_types=1);

/** @var array $config */
/** @var ApiClient $api */

$pageTitle = $pageTitle ?? app_name($config);
$activeNav = $activeNav ?? '';
$user = Auth::user();
$me = $_SESSION['api_me'] ?? null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="wrap">
    <?php if ($user): ?>
    <header class="topbar">
        <div class="brand"><?= e(app_name($config)) ?></div>
        <nav class="nav">
            <a href="dashboard.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">Panel</a>
            <?php if (can('customer')): ?>
            <a href="customer.php" class="<?= $activeNav === 'customer' ? 'active' : '' ?>">Müşteri</a>
            <?php endif; ?>
            <?php if (can('directory')): ?>
            <a href="directory.php" class="<?= $activeNav === 'directory' ? 'active' : '' ?>">Rehber</a>
            <?php endif; ?>
        </nav>
        <div class="user">
            <?php if (is_array($me) && ! empty($me['customer']['company_name'])): ?>
            <span class="muted"><?= e($me['customer']['company_name']) ?></span>
            <?php endif; ?>
            <span><?= e($user) ?></span>
            <a href="logout.php">Çıkış</a>
        </div>
    </header>
    <?php endif; ?>

    <main class="main">
        <?php if ($msg = flash('success')): ?>
        <div class="alert ok"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash('error')): ?>
        <div class="alert err"><?= e($msg) ?></div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>
</div>
</body>
</html>
