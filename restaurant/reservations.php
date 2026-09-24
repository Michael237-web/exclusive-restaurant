<?php
require_once __DIR__.'/admin-auth.php';
$page_title = 'Reservations';

if(isset($_GET['status'], $_GET['id'])){
  $pdo->prepare("UPDATE restaurant_reservations SET status=? WHERE id=?")
      ->execute([$_GET['status'], (int)$_GET['id']]);
  redirect('reservations.php');
}
$rows = $pdo->query("SELECT * FROM restaurant_reservations ORDER BY res_date DESC, res_time DESC")->fetchAll();
require __DIR__.'/admin-header.php';
?>
<h1>Reservations</h1>
<table class="cart-table">
  <tr><th>Name</th><th>Phone</th><th>Date</th><th>Time</th><th>Guests</th><th>Status</th><th>Actions</th></tr>
  <?php foreach($rows as $r): ?>
    <tr>
      <td><?= e($r['name']) ?></td>
      <td><?= e($r['phone']) ?></td>
      <td><?= e($r['res_date']) ?></td>
      <td><?= e($r['res_time']) ?></td>
      <td><?= (int)$r['guests'] ?></td>
      <td><?= e($r['status']) ?></td>
      <td>
        <a href="?status=confirmed&id=<?= $r['id'] ?>">✅ Confirm</a> |
        <a href="?status=cancelled&id=<?= $r['id'] ?>">❌ Cancel</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__.'/admin-footer.php'; ?>