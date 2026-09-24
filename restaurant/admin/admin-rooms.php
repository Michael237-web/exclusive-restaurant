<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';
require_once __DIR__.'/auth.php';

require_admin();

$page_title = 'Rooms & Bookings | Admin';

/* Handle status updates */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id && in_array($status, ['pending','confirmed','cancelled','completed'])) {
        $st = $pdo->prepare("UPDATE restaurant_room_bookings SET status=? WHERE id=?");
        $st->execute([$status, $id]);
    }
    redirect(BASE_URL.'/admin-rooms.php');
}

$bookings = $pdo->query("
    SELECT b.*, r.name AS room_name, r.room_type
    FROM restaurant_room_bookings b
    JOIN restaurant_rooms r ON r.id = b.room_id
    ORDER BY b.created_at DESC
    LIMIT 100
")->fetchAll();

$rooms = $pdo->query("SELECT * FROM restaurant_rooms ORDER BY name")->fetchAll();

require __DIR__.'/header.php';
?>

<section class="container section">
  <h1>Rooms &amp; Bookings</h1>

  <h2 style="margin-top:32px">Recent Bookings</h2>
  <div class="table-scroll">
    <table class="cart-table">
      <thead>
        <tr>
          <th>Ref</th><th>Room</th><th>Guest</th>
          <th>Dates</th><th>Nights</th><th>Total</th>
          <th>Status</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($bookings as $b): ?>
        <tr>
          <td><code><?= e($b['booking_ref']) ?></code></td>
          <td><?= e($b['room_name']) ?><br><small><?= e($b['room_type']) ?></small></td>
          <td>
            <?= e($b['guest_name']) ?><br>
            <small><?= e($b['guest_phone']) ?></small>
          </td>
          <td><?= e($b['check_in']) ?> →<br><?= e($b['check_out']) ?></td>
          <td><?= (int)$b['nights'] ?></td>
          <td>KSh <?= number_format((float)$b['total_amount']) ?></td>
          <td><span class="status status-<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
          <td>
            <form method="post" style="display:inline-flex;gap:6px">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <select name="status" style="margin:0;padding:6px 10px">
                <?php foreach (['pending','confirmed','cancelled','completed'] as $s): ?>
                  <option value="<?= $s ?>" <?= $b['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-primary btn-sm">Save</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$bookings): ?>
        <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--muted)">No bookings yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <h2 style="margin-top:48px">Rooms</h2>
  <div class="table-scroll">
    <table class="cart-table">
      <thead>
        <tr>
          <th>Name</th><th>Type</th><th>Price/night</th>
          <th>Sleeps</th><th>Units</th><th>Active</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rooms as $r): ?>
        <tr>
          <td><?= e($r['name']) ?></td>
          <td><?= e($r['room_type']) ?></td>
          <td>KSh <?= number_format((float)$r['price_per_night']) ?></td>
          <td><?= (int)$r['capacity'] ?></td>
          <td><?= (int)$r['total_units'] ?></td>
          <td><?= $r['active'] ? '✅' : '❌' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__.'/footer.php'; ?>