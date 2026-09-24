<?php
require_once __DIR__.'/admin-auth.php';
$page_title = 'Order Detail';
$id = (int)$_GET['id'];

$st = $pdo->prepare("SELECT * FROM restaurant_orders WHERE id=?");
$st->execute([$id]);
$o = $st->fetch();
if(!$o) die('Not found');

$it = $pdo->prepare("SELECT * FROM restaurant_order_items WHERE order_id=?");
$it->execute([$id]);
$items = $it->fetchAll();

require __DIR__.'/admin-header.php';
?>
<h1>Order <?= e($o['order_ref']) ?></h1>
<p><strong>Customer:</strong> <?= e($o['customer_name']) ?> — <?= e($o['phone']) ?></p>
<p><strong>Type:</strong> <?= e($o['order_type']) ?></p>
<p><strong>Address:</strong> <?= e($o['address']) ?></p>
<p><strong>Notes:</strong> <?= e($o['notes']) ?></p>

<table class="cart-table">
  <?php foreach($items as $i): ?>
    <tr><td><?= e($i['name']) ?> × <?= $i['quantity'] ?></td><td><?= money($i['price'] * $i['quantity']) ?></td></tr>
  <?php endforeach; ?>
  <tr><td>Subtotal</td><td><?= money($o['subtotal']) ?></td></tr>
  <tr><td>Delivery</td><td><?= money($o['delivery_fee']) ?></td></tr>
  <tr><td><strong>Total</strong></td><td><strong><?= money($o['total']) ?></strong></td></tr>
</table>
<a href="orders.php" class="btn btn-outline">Back</a>
<?php require __DIR__.'/admin-footer.php'; ?>