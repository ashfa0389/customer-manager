<?php
// Top of every logged-in page. Set $pageTitle and $navActive before including this file.
$user    = current_user();
$flashes = take_flash();

$navItems = [
    ['dashboard', 'Dashboard', 'dashboard.php', 'grid'],
    ['customers', 'Customers', 'customers.php', 'users'],
    ['hosting',   'Hosting',   'hosting.php',   'server'],
    ['web_plans', 'Web plans', 'web-plans.php', 'globe'],
    ['backups',   'Backups',   'backups.php',   'database'],
    ['payments',  'Payments',  'payments.php',  'card'],
];
if (is_admin()) {
    $navItems[] = ['staff', 'Staff', 'staff.php', 'staff'];
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(isset($pageTitle) ? $pageTitle : 'Dashboard') ?> - <?= e(APP_NAME . ' ' . APP_SUBTITLE) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=1">
</head>
<body>
<div class="app">

  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <span class="brand-mark"><?= icon('globe', 20) ?></span>
      <span class="brand-text"><?= e(APP_NAME) ?><small><?= e(APP_SUBTITLE) ?></small></span>
    </div>

    <nav class="nav" aria-label="Main">
      <?php foreach ($navItems as $item): ?>
        <a href="<?= e($item[2]) ?>" class="<?= (isset($navActive) && $navActive === $item[0]) ? 'active' : '' ?>">
          <?= icon($item[3], 20) ?> <span><?= e($item[1]) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="side-foot">
      <div class="who">
        <?= e($user['name']) ?>
        <small><?= $user['role'] === 'admin' ? 'Admin' : 'Staff' ?></small>
      </div>
      <form method="post" action="logout.php">
        <?= csrf_field() ?>
        <button type="submit" class="logout"><?= icon('logout', 18) ?> Log out</button>
      </form>
    </div>
  </aside>

  <div class="content">
    <header class="topbar">
      <button type="button" class="icon-btn" id="menuToggle" aria-label="Open menu"><?= icon('menu', 22) ?></button>
      <strong><?= e(isset($pageTitle) ? $pageTitle : 'Dashboard') ?></strong>
    </header>

    <main class="page">
      <?php if ($flashes): ?>
        <div class="flashes">
          <?php foreach ($flashes as $f): ?>
            <div class="flash <?= e($f['type']) ?>" role="status">
              <span><?= e($f['msg']) ?></span>
              <button type="button" data-dismiss aria-label="Dismiss"><?= icon('x', 16) ?></button>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
