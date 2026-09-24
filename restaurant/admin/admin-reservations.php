<?php
require_once __DIR__.'/admin-auth.php';
$page_title = 'Reservations';

if (isset($_GET['status']) && isset($_GET['id'])) {
    $pdo->prepare("UPDATE restaurant_reservations SET status=? WHERE id=?")
        ->execute([$_GET['status'], (int)$_GET['id']]);
    flash('success', 'Reservation updated.');
    redirect(BASE_URL.'/admin-reservations.php');
}

$rows = $pdo->query("SELECT r.*, b.name branch FROM restaurant_reservations r
                     LEFT JOIN restaurant_branches b ON b.id=r.branch_id
                     ORDER BY r.created_at DESC")->fetchAll();

require __DIR__.'/admin-header.php';
?>

<div class="table-scroll">
<table class="cart-table">
  <tr><th>Name</th><th>Phone</th><th>Date</th><th>Time</th><th>Guests</th><th>Branch</th><th>Status</th><th>Actions</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['name']) ?></td>
      <td><?= e($r['phone']) ?></td>
      <td><?= e($r['res_date']) ?></td>
      <td><?= e($r['res_time']) ?></td>
      <td><?= (int)$r['guests'] ?></td>
      <td><?= e($r['branch']) ?></td>
      <td><span class="status status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
      <td>
        <select onchange="location.href='<?= BASE_URL ?>/admin-reservations.php?status='+this.value+'&id=<?= (int)$r['id'] ?>'">
          <?php foreach (['pending','confirmed','seated','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
</div>

<?php require __DIR__.'/admin-footer.php'; ?>