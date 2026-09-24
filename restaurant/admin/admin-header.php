<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/auth.php';

// Admin pages use require_admin() themselves, but we make sure it's called.
require_admin();

$adminUser  = current_user();
$site_name  = setting($pdo, 'site_name', 'Restaurant');
$page_title = $page_title ?? 'Admin';
$current    = basename($_SERVER['PHP_SELF']);

$menu = [
  'admin-dashboard.php'     => ['Dashboard',   'star'],
  'admin-orders.php'        => ['Orders',      'cart'],
  'admin-reservations.php'  => ['Reservations','calendar'],
  'admin-menu-list.php'     => ['Menu Items',  'utensils'],
  'admin-categories.php'    => ['Categories',  'menu'],
  'admin-promotions.php'    => ['Promotions',  'award'],
  'admin-reviews.php'       => ['Reviews',     'quote'],
  'admin-branches.php'      => ['Branches',    'pin'],
  'admin-customers.php'     => ['Customers',   'user'],
  'admin-settings.php'      => ['Settings',    'sun'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> · <?= e($site_name) ?> Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
</head>
<body class="admin-body">

<aside class="admin-sidebar">
  <div class="admin-brand">
    <span class="logo-mark"><?= icon('utensils', 18) ?></span>
    <span><?= e($site_name) ?></span>
  </div>

  <nav class="admin-nav">
    <?php foreach ($menu as $file => [$label, $ic]): ?>
      <a href="<?= BASE_URL ?>/<?= e($file) ?>"
         class="<?= $current === $file ? 'is-active' : '' ?>">
        <?= icon($ic, 16) ?> <span><?= e($label) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="admin-user">
    <small>Signed in as</small>
    <strong><?= e($adminUser['name']) ?></strong>
    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-dark btn-sm">Logout</a>
  </div>
</aside>

<main class="admin-main">
  <header class="admin-topbar">
    <h1><?= e($page_title) ?></h1>
    <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-outline-dark btn-sm">
      <?= icon('arrow', 14) ?> View site
    </a>
  </header>

  <?php
  $flash_ok  = flash('success');
  $flash_err = flash('error');
  if ($flash_ok):  ?><div class="alert success"><?= e($flash_ok) ?></div><?php endif;
  if ($flash_err): ?><div class="alert error"><?= e($flash_err) ?></div><?php endif;
  ?>