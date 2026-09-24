<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';
require_once __DIR__.'/auth.php';

$page_title = 'Reserve a Table | ' . setting($pdo, 'site_name', 'Restaurant');
$branches = $pdo->query("SELECT id,name FROM restaurant_branches WHERE active=1")->fetchAll();

$done = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $user = current_user();
    try {
        $st = $pdo->prepare("INSERT INTO restaurant_reservations
          (user_id, name, phone, email, res_date, res_time, guests, seating, request, branch_id)
          VALUES (?,?,?,?,?,?,?,?,?,?)");
        $st->execute([
            $user['id'] ?? null,
            trim($_POST['name']),
            trim($_POST['phone']),
            trim($_POST['email'] ?? ''),
            $_POST['date'],
            $_POST['time'],
            (int)$_POST['guests'],
            $_POST['seating'] ?? null,
            $_POST['request'] ?? null,
            (int)$_POST['branch_id'],
        ]);
        $done = true;
    } catch (PDOException $e) {
        error_log('reservation failed: '.$e->getMessage());
        $error = 'Sorry, we could not save your reservation. Please try again.';
    }
}

require __DIR__.'/header.php';
?>

<section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1920&q=80')">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <p class="hero-eyebrow">Book a table</p>
    <h1>Reserve Your Table</h1>
    <p class="hero-sub">The perfect spot for any occasion</p>
  </div>
</section>

<section class="container section" style="max-width:820px">
  <?php if ($done): ?>
    <div class="alert success" role="status">
      <?= icon('check', 16) ?> Reservation received! We'll confirm shortly via phone or email.
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert error" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" class="reserve-form">
    <?= csrf_field() ?>
    <div class="grid grid-2">
      <label>Name *<input type="text" name="name" required value="<?= e($user['name'] ?? '') ?>"></label>
      <label>Phone *<input type="tel" name="phone" required value="<?= e($user['phone'] ?? '') ?>"></label>
      <label>Email <input type="email" name="email" value="<?= e($user['email'] ?? '') ?>"></label>
      <label>Date *<input type="date" name="date" required min="<?= date('Y-m-d') ?>"></label>
      <label>Time *<input type="time" name="time" required></label>
      <label>Guests *<input type="number" name="guests" min="1" max="50" value="2" required></label>
      <label>Seating
        <select name="seating">
          <option>Indoor</option>
          <option>Outdoor</option>
          <option>Private Room</option>
          <option>Bar</option>
        </select>
      </label>
      <label>Branch
        <select name="branch_id">
          <?php foreach ($branches as $b): ?>
            <option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <label>Special Request <textarea name="request" rows="3"></textarea></label>
    <button type="submit" class="btn btn-primary btn-lg">Confirm Reservation</button>
  </form>
</section>

<?php require __DIR__.'/footer.php'; ?>