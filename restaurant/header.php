<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';
require_once __DIR__.'/auth.php';

$user      = current_user();
$site_name = setting($pdo, 'site_name', 'Restaurant');
$wa        = preg_replace('/\D+/', '', setting($pdo, 'whatsapp', '254700000000'));
$cartCount = array_sum(array_column($_SESSION['cart'] ?? [], 'qty'));

$page_title = $page_title ?? 'Exclusive Restaurant';
$page_desc  = $page_desc  ?? 'Authentic Kenyan & International Cuisine in Nairobi. Order online, reserve a table, or find your nearest branch.';
$og_image   = $og_image   ?? logo_url();
$canonical  = BASE_URL . '/' . ltrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '', '/');

$deliveryFee = $deliveryFee ?? (int)setting($pdo, 'delivery_fee', 200);
$logoUrl     = logo_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0e0e10">

<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($page_desc) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="delivery-fee" content="<?= (int)$deliveryFee ?>">

<meta property="og:type"        content="website">
<meta property="og:site_name"   content="Exclusive Restaurant">
<meta property="og:title"       content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($page_desc) ?>">
<meta property="og:image"       content="<?= e($og_image) ?>">
<meta property="og:url"         content="<?= e($canonical) ?>">
<meta name="twitter:card"       content="summary_large_image">

<link rel="icon" href="<?= e($logoUrl) ?>" type="image/png">
<link rel="apple-touch-icon" href="<?= e($logoUrl) ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Great+Vibes&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">

<?php if (!empty($page_head)) echo $page_head; ?>
</head>
<body data-delivery-fee="<?= (int)$deliveryFee ?>">

<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header" id="siteHeader" role="banner">
  <div class="container nav-wrap">

    <!-- ============ LOGO + TAGLINE ============ -->
    <a href="<?= e(clean_url('')) ?>" class="logo" aria-label="Exclusive Restaurant — home">
      <span class="logo-brand">
        <img src="<?= e($logoUrl) ?>"
             alt="Exclusive Restaurant"
             class="logo-img"
             onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
        <span class="logo-fallback">
          <span class="logo-mark" aria-hidden="true"><?= icon('utensils', 20) ?></span>
          <span class="logo-text">Exclusive Restaurant</span>
        </span>
        <span class="logo-tagline">
          <span class="logo-tagline-text">Exclusive Restaurant</span>
        </span>
      </span>
    </a>

    <!-- ============ MOBILE TOGGLE ============ -->
    <button class="nav-toggle" type="button"
            aria-label="Open navigation"
            aria-expanded="false"
            aria-controls="primary-nav">
      <span class="nav-toggle-open"><?= icon('menu', 22) ?></span>
      <span class="nav-toggle-close"><?= icon('close', 22) ?></span>
    </button>

    <!-- ============ NAV ============ -->
    <nav id="primary-nav" class="nav" role="navigation" aria-label="Primary">
      <div class="nav-links">
        <?php
          $links = [
  ''             => 'Home',
  'menu.php'     => 'Menu',
  'rooms.php'    => 'Rooms',
  'offers.php'   => 'Offers',
  'gallery.php'  => 'Gallery',
  'events.php'   => 'Events',
  'about.php'    => 'About',
  'branches.php' => 'Branches',
  'contact.php'  => 'Contact',
];
          foreach ($links as $file => $label):
            $href   = clean_url($file);
            $active = $file === '' ? is_active('index.php') : is_active($file);
        ?>
          <a href="<?= e($href) ?>"
             class="nav-link <?= $active ? 'is-active' : '' ?>">
            <?= e($label) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="nav-actions">
        <!-- Cart -->
        <a href="<?= e(clean_url('cart.php')) ?>"
           class="cart-link <?= is_active('cart.php','checkout.php') ? 'is-active' : '' ?>"
           aria-label="Cart, <?= (int)$cartCount ?> items">
          <?= icon('cart', 20) ?>
          <span id="cart-count" class="cart-count" data-count="<?= (int)$cartCount ?>"><?= (int)$cartCount ?></span>
        </a>

        <!-- Account / Login -->
        <?php if ($user): ?>
          <a href="<?= e(clean_url('account.php')) ?>" class="nav-account">
            <?= icon('user', 18) ?>
            <span>Hi, <?= e(explode(' ', trim($user['name']))[0]) ?></span>
          </a>
        <?php else: ?>
          <a href="<?= e(clean_url('login.php')) ?>" class="btn btn-primary btn-sm nav-login">
            <?= icon('user', 16) ?> Login
          </a>
        <?php endif; ?>

        <!-- Theme toggle -->
        <button id="themeToggle" class="theme-toggle"
                type="button"
                title="Toggle dark mode"
                aria-label="Toggle dark mode">
          <span class="icon-moon"><?= icon('moon', 18) ?></span>
          <span class="icon-sun"><?= icon('sun', 18) ?></span>
        </button>
      </div>
    </nav>
  </div>
</header>

<main id="main" role="main">
<?php
$flash_ok  = flash('success');
$flash_err = flash('error');
if ($flash_ok):  ?><div class="container flash-wrap"><div class="alert success" role="status"><?= e($flash_ok) ?></div></div><?php endif;
if ($flash_err): ?><div class="container flash-wrap"><div class="alert error" role="alert"><?= e($flash_err) ?></div></div><?php endif;
?>