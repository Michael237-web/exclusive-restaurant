<?php
require_once __DIR__.'/admin-auth.php';
$page_title = 'Orders';

if(isset($_GET['status']) && isset($_GET['id'])){
  $pdo->prepare("UPDATE restaurant_orders SET status=? WHERE id=?")
      ->execute([$_GET['status'], (int)$_GET['id']]);
  redirect('orders.php');
}

$orders = $pdo->query("SELECT * FROM restaurant_orders ORDER BY created_at DESC LIMIT 200")->fetchAll();
require __DIR__.'/admin-header.php';
?>
<h1>Orders</h1>
<table class="cart-table">
  <tr><th>Ref</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th></tr>
  <?php foreach($orders as $o): ?>
    <tr>
      <td><a href="order-view.php?id=<?= $o['id'] ?>"><?= e($o['order_ref']) ?></a></td>
      <td><?= e($o['customer_name']) ?><br><small><?= e($o['phone']) ?></small></td>
      <td><?= money($o['total']) ?></td>
      <td><?= e($o['payment_status']) ?></td>
      <td>
        <select onchange="location.href='orders.php?status='+this.value+'&id=<?= $o['id'] ?>'">
          <?php foreach(['received','preparing','ready','out_for_delivery','delivered','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td><?= date('d M H:i', strtotime($o['created_at'])) ?></td>
      <td><a href="order-view.php?id=<?= $o['id'] ?>">View</a></td>
    </tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__.'/admin-footer.php'; ?>