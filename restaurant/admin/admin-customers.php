<?php
require_once __DIR__.'/admin-auth.php';
$page_title = 'Customers';

$customers = $pdo->query("
  SELECT u.*,
    (SELECT COUNT(*) FROM restaurant_orders o WHERE o.user_id=u.id) order_count,
    (SELECT IFNULL(SUM(total),0) FROM restaurant_orders o WHERE o.user_id=u.id AND o.payment_status='paid') total_spent
  FROM restaurant_users u
  WHERE u.role='customer'
  ORDER BY u.created_at DESC
")->fetchAll();

require __DIR__.'/admin-header.php';
?>

<div class="table-scroll">
<table class="cart-table">
  <tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Points</th><th>Joined</th></tr>
  <?php foreach ($customers as $c): ?>
    <tr>
      <td><?= e($c['name']) ?></td>
      <td><?= e($c['email']) ?></td>
      <td><?= e($c['phone']) ?></td>
      <td><?= (int)$c['order_count'] ?></td>
      <td><?= money($c['total_spent']) ?></td>
      <td><?= (int)$c['loyalty_points'] ?></td>
      <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$customers): ?><tr><td colspan="7">No customers yet.</td></tr><?php endif; ?>
</table>
</div>

<?php require __DIR__.'/admin-footer.php'; ?>