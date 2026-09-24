<?php
require_once __DIR__.'/admin-auth.php';
$page_title = 'Dashboard';

$todaySales = (int)$pdo->query("SELECT IFNULL(SUM(total),0) FROM restaurant_orders WHERE DATE(created_at)=CURDATE() AND payment_status='paid'")->fetchColumn();
$todayOrders = (int)$pdo->query("SELECT COUNT(*) FROM restaurant_orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$pendingResv = (int)$pdo->query("SELECT COUNT(*) FROM restaurant_reservations WHERE status='pending'")->fetchColumn();
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM restaurant_users WHERE role='customer'")->fetchColumn();

$popular = $pdo->query("SELECT oi.name, SUM(oi.quantity) q FROM restaurant_order_items oi
                        JOIN restaurant_orders o ON o.id=oi.order_id
                        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY oi.name ORDER BY q DESC LIMIT 1")->fetchColumn();

require __DIR__.'/admin-header.php';
?>
<h1>Dashboard</h1>
<div class="grid grid-4">
  <div class="stat-card"><small>Today's Sales</small><h2><?= money($todaySales) ?></h2></div>
  <div class="stat-card"><small>Orders Today</small><h2><?= $todayOrders ?></h2></div>
  <div class="stat-card"><small>Pending Reservations</small><h2><?= $pendingResv ?></h2></div>
  <div class="stat-card"><small>Total Customers</small><h2><?= $totalCustomers ?></h2></div>
</div>
<p style="margin-top:20px">🔥 Most popular dish (30 days): <strong><?= e($popular ?: '—') ?></strong></p>
<?php require __DIR__.'/admin-footer.php'; ?>