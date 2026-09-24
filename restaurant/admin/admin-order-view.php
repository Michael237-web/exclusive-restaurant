<?php
require_once __DIR__.'/admin-auth.php';
$page_title = 'Order Detail';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM restaurant_orders WHERE id=?");
$st->execute([$id]);
$o = $st->fetch();
if (!$o) die('Not found');

$it = $pdo->prepare("SELECT * FROM restaurant_order_items WHERE order_id=?");
$it->execute([$id]);
$items = $it->fetchAll();

require __DIR__.'/admin-header.php';
?>

<div class="grid grid-2">
  <div class="stat-card">
    <small>Customer</small>
    <h2 style="font-size:1.1rem"><?= e($o['customer_name']) ?></h2>
    <p><?= e($o['phone']) ?> · <?= e($o['email']) ?></p>
  </div>
  <div class="stat-card">
    <small>Type / Address</small>
    <h2 style="font-size:1.1rem"><?= e(ucfirst($o['order_type'])) ?></h2>
    <p><?= e($o['address']) ?></p>
  </div>
</div>

<?php if ($o['notes']): ?>
  <div class="stat-card" style="margin-top:20px">
    <small>Special notes</small>
    <p style="margin-top:8px"><?= nl2br(e($o['notes'])) ?></p>
  </div>
<?php endif; ?>

<h2 style="margin:30px 0 10px;font-size:1.1rem">Items</h2>
<table class="cart-table">
  <?php foreach ($items as $i): ?>
    <tr><td><?= e($i['name']) ?> × <?= (int)$i['quantity'] ?></td><td><?= money($i['price'] * $i['quantity']) ?></td></tr>
  <?php endforeach; ?>
  <tr><td>Subtotal</td><td><?= money($o['subtotal']) ?></td></tr>
  <tr><td>Delivery</td><td><?= money($o['delivery_fee']) ?></td></tr>
  <tr class="grand"><td><strong>Total</strong></td><td><strong><?= money($o['total']) ?></strong></td></tr>
</table>

<a href="<?= BASE_URL ?>/admin-orders.php" class="btn btn-outline-dark">← Back to Orders</a>

<?php require __DIR__.'/admin-footer.php'; ?>