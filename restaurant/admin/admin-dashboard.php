<?php
require_once __DIR__.'/admin-auth.php';
$page_title = 'Dashboard';

$todaySales     = (int)$pdo->query("SELECT IFNULL(SUM(total),0) FROM restaurant_orders WHERE DATE(created_at)=CURDATE() AND payment_status='paid'")->fetchColumn();
$todayOrders    = (int)$pdo->query("SELECT COUNT(*) FROM restaurant_orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$pendingResv    = (int)$pdo->query("SELECT COUNT(*) FROM restaurant_reservations WHERE status='pending'")->fetchColumn();
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM restaurant_users WHERE role='customer'")->fetchColumn();

$popular = $pdo->query("SELECT oi.name, SUM(oi.quantity) q FROM restaurant_order_items oi
                        JOIN restaurant_orders o ON o.id=oi.order_id
                        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY oi.name ORDER BY q DESC LIMIT 1")->fetchColumn();

$recent = $pdo->query("SELECT * FROM restaurant_orders ORDER BY created_at DESC LIMIT 8")->fetchAll();

require __DIR__.'/admin-header.php';
?>

<div class="grid grid-4">
  <div class="stat-card"><small>Today's Sales</small><h2><?= money($todaySales) ?></h2></div>
  <div class="stat-card"><small>Orders Today</small><h2><?= $todayOrders ?></h2></div>
  <div class="stat-card"><small>Pending Reservations</small><h2><?= $pendingResv ?></h2></div>
  <div class="stat-card"><small>Total Customers</small><h2><?= $totalCustomers ?></h2></div>
</div>

<div class="stat-card" style="margin-top:24px">
  <small>Most popular dish (30 days)</small>
  <h2><?= e($popular ?: '—') ?></h2>
</div>

<h2 style="margin:36px 0 14px;font-size:1.2rem">Recent Orders</h2>
<div class="table-scroll">
  <table class="cart-table">
    <tr><th>Ref</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr>
    <?php foreach ($recent as $o): ?>
      <tr>
        <td><a href="<?= BASE_URL ?>/admin-order-view.php?id=<?= (int)$o['id'] ?>"><code><?= e($o['order_ref']) ?></code></a></td>
        <td><?= e($o['customer_name']) ?></td>
        <td><?= money($o['total']) ?></td>
        <td><span class="status status-<?= e($o['payment_status']) ?>"><?= e($o['payment_status']) ?></span></td>
        <td><span class="status status-<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
        <td><?= date('d M H:i', strtotime($o['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require __DIR__.'/admin-footer.php'; ?>