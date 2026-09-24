<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/auth.php';
require_login();

$page_title = 'My Account | ' . setting($pdo, 'site_name', 'Restaurant');
$user = current_user();

$orders = $pdo->prepare("SELECT * FROM restaurant_orders WHERE user_id=? ORDER BY created_at DESC");
$orders->execute([$user['id']]);
$orders = $orders->fetchAll();

$resv = $pdo->prepare("SELECT * FROM restaurant_reservations WHERE user_id=? ORDER BY created_at DESC");
$resv->execute([$user['id']]);
$resv = $resv->fetchAll();

require __DIR__.'/header.php';
?>

<section class="container section">
  <h1>Hi, <?= e($user['name']) ?></h1>
  <div class="account-summary">
    <div class="stat-card"><small>Loyalty Points</small><h2><?= (int)$user['loyalty_points'] ?></h2></div>
    <div class="stat-card"><small>Total Orders</small><h2><?= count($orders) ?></h2></div>
    <div class="stat-card"><small>Reservations</small><h2><?= count($resv) ?></h2></div>
  </div>

  <h2>My Orders</h2>
  <?php if (!$orders): ?>
    <p class="empty-state">You haven't placed any orders yet. <a href="<?= BASE_URL ?>/menu.php">Browse the menu →</a></p>
  <?php else: ?>
    <div class="table-scroll">
      <table class="cart-table">
        <tr><th>Ref</th><th>Total</th><th>Status</th><th>Payment</th><th>Date</th><th></th></tr>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><code><?= e($o['order_ref']) ?></code></td>
            <td><?= money($o['total']) ?></td>
            <td><span class="status status-<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
            <td><?= e($o['payment_status']) ?></td>
            <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            <td><a href="<?= BASE_URL ?>/order-track.php?ref=<?= e($o['order_ref']) ?>" class="btn btn-outline-dark btn-sm">Track</a></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>

  <h2>My Reservations</h2>
  <?php if (!$resv): ?>
    <p class="empty-state">No reservations yet. <a href="<?= BASE_URL ?>/reserve.php">Book a table →</a></p>
  <?php else: ?>
    <div class="table-scroll">
      <table class="cart-table">
        <tr><th>Date</th><th>Time</th><th>Guests</th><th>Status</th></tr>
        <?php foreach ($resv as $r): ?>
          <tr>
            <td><?= e($r['res_date']) ?></td>
            <td><?= e($r['res_time']) ?></td>
            <td><?= (int)$r['guests'] ?></td>
            <td><span class="status status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>

  <p style="margin-top:40px">
    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-dark">Logout</a>
  </p>
</section>

<?php require __DIR__.'/footer.php'; ?>