<?php
require_once __DIR__.'/admin-auth.php';
require_once __DIR__.'/csrf.php';
$page_title = 'Branches';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!empty($_POST['id'])) {
        $pdo->prepare("UPDATE restaurant_branches SET name=?,address=?,phone=?,email=?,hours=?,map_embed=?,active=? WHERE id=?")
            ->execute([$_POST['name'], $_POST['address'], $_POST['phone'], $_POST['email'], $_POST['hours'], $_POST['map_embed'], !empty($_POST['active']) ? 1 : 0, (int)$_POST['id']]);
        flash('success', 'Branch updated.');
    } else {
        $pdo->prepare("INSERT INTO restaurant_branches (name,address,phone,email,hours,map_embed,active) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['name'], $_POST['address'], $_POST['phone'], $_POST['email'], $_POST['hours'], $_POST['map_embed'], !empty($_POST['active']) ? 1 : 0]);
        flash('success', 'Branch added.');
    }
    redirect(BASE_URL.'/admin-branches.php');
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM restaurant_branches WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success', 'Branch deleted.');
    redirect(BASE_URL.'/admin-branches.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM restaurant_branches WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}

$rows = $pdo->query("SELECT * FROM restaurant_branches ORDER BY name")->fetchAll();
require __DIR__.'/admin-header.php';
?>

<form method="post" class="checkout-form" style="max-width:600px;margin-bottom:30px">
  <?= csrf_field() ?>
  <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
  <label>Name <input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required></label>
  <label>Address <input type="text" name="address" value="<?= e($edit['address'] ?? '') ?>"></label>
  <label>Phone <input type="text" name="phone" value="<?= e($edit['phone'] ?? '') ?>"></label>
  <label>Email <input type="email" name="email" value="<?= e($edit['email'] ?? '') ?>"></label>
  <label>Hours <input type="text" name="hours" value="<?= e($edit['hours'] ?? '') ?>"></label>
  <label>Map Embed (iframe HTML) <textarea name="map_embed" rows="3"><?= e($edit['map_embed'] ?? '') ?></textarea></label>
  <label style="margin-top:10px"><input type="checkbox" name="active" <?= !empty($edit['active']) ? 'checked' : '' ?>> Active</label>
  <button class="btn btn-primary"><?= $edit ? 'Update' : 'Add' ?> Branch</button>
  <?php if ($edit): ?><a href="<?= BASE_URL ?>/admin-branches.php" class="btn btn-outline-dark">Cancel</a><?php endif; ?>
</form>

<div class="table-scroll">
<table class="cart-table">
  <tr><th>Name</th><th>Address</th><th>Phone</th><th>Hours</th><th>Active</th><th>Actions</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['name']) ?></td>
      <td><?= e($r['address']) ?></td>
      <td><?= e($r['phone']) ?></td>
      <td><?= e($r['hours']) ?></td>
      <td><?= $r['active'] ? '✅' : '❌' ?></td>
      <td>
        <a href="<?= BASE_URL ?>/admin-branches.php?edit=<?= (int)$r['id'] ?>">Edit</a> ·
        <a href="<?= BASE_URL ?>/admin-branches.php?delete=<?= (int)$r['id'] ?>">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
</div>

<?php require __DIR__.'/admin-footer.php'; ?>