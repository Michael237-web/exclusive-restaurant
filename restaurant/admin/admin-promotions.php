<?php
require_once __DIR__.'/admin-auth.php';
require_once __DIR__.'/csrf.php';
$page_title = 'Promotions';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $img = null;
    if (!empty($_FILES['image']['name'])) $img = upload_image($_FILES['image'], 'menu');

    if (!empty($_POST['id'])) {
        if ($img) {
            $pdo->prepare("UPDATE restaurant_promotions SET title=?,description=?,code=?,discount_percent=?,expires_at=?,active=?,image=? WHERE id=?")
                ->execute([$_POST['title'], $_POST['description'], $_POST['code'] ?: null, (int)$_POST['discount_percent'], $_POST['expires_at'] ?: null, !empty($_POST['active']) ? 1 : 0, $img, (int)$_POST['id']]);
        } else {
            $pdo->prepare("UPDATE restaurant_promotions SET title=?,description=?,code=?,discount_percent=?,expires_at=?,active=? WHERE id=?")
                ->execute([$_POST['title'], $_POST['description'], $_POST['code'] ?: null, (int)$_POST['discount_percent'], $_POST['expires_at'] ?: null, !empty($_POST['active']) ? 1 : 0, (int)$_POST['id']]);
        }
        flash('success', 'Promotion updated.');
    } else {
        $pdo->prepare("INSERT INTO restaurant_promotions (title,description,code,discount_percent,expires_at,active,image) VALUES (?,?,?,?,?,?,?)")
            ->execute([$_POST['title'], $_POST['description'], $_POST['code'] ?: null, (int)$_POST['discount_percent'], $_POST['expires_at'] ?: null, !empty($_POST['active']) ? 1 : 0, $img]);
        flash('success', 'Promotion created.');
    }
    redirect(BASE_URL.'/admin-promotions.php');
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM restaurant_promotions WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success', 'Promotion deleted.');
    redirect(BASE_URL.'/admin-promotions.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM restaurant_promotions WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}

$rows = $pdo->query("SELECT * FROM restaurant_promotions ORDER BY created_at DESC")->fetchAll();

require __DIR__.'/admin-header.php';
?>

<form method="post" enctype="multipart/form-data" class="checkout-form" style="max-width:600px;margin-bottom:30px">
  <?= csrf_field() ?>
  <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
  <label>Title <input type="text" name="title" value="<?= e($edit['title'] ?? '') ?>" required></label>
  <label>Description <textarea name="description" rows="3"><?= e($edit['description'] ?? '') ?></textarea></label>
  <label>Coupon Code <input type="text" name="code" value="<?= e($edit['code'] ?? '') ?>"></label>
  <label>Discount % <input type="number" name="discount_percent" min="0" max="100" value="<?= e($edit['discount_percent'] ?? 0) ?>"></label>
  <label>Expires At <input type="date" name="expires_at" value="<?= e($edit['expires_at'] ?? '') ?>"></label>
  <label>Image <input type="file" name="image" accept="image/*"></label>
  <?php if (!empty($edit['image'])): ?><img src="<?= UPLOAD_URL . e($edit['image']) ?>" style="max-width:120px;border-radius:8px"><?php endif; ?>
  <label style="margin-top:10px"><input type="checkbox" name="active" <?= !empty($edit['active']) ? 'checked' : '' ?>> Active</label>
  <button class="btn btn-primary"><?= $edit ? 'Update' : 'Add' ?> Promotion</button>
  <?php if ($edit): ?><a href="<?= BASE_URL ?>/admin-promotions.php" class="btn btn-outline-dark">Cancel</a><?php endif; ?>
</form>

<div class="table-scroll">
<table class="cart-table">
  <tr><th>Title</th><th>Code</th><th>Discount</th><th>Expires</th><th>Active</th><th>Actions</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['title']) ?></td>
      <td><code><?= e($r['code']) ?></code></td>
      <td><?= (int)$r['discount_percent'] ?>%</td>
      <td><?= e($r['expires_at']) ?></td>
      <td><?= $r['active'] ? '✅' : '❌' ?></td>
      <td>
        <a href="<?= BASE_URL ?>/admin-promotions.php?edit=<?= (int)$r['id'] ?>">Edit</a> ·
        <a href="<?= BASE_URL ?>/admin-promotions.php?delete=<?= (int)$r['id'] ?>">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
</div>

<?php require __DIR__.'/admin-footer.php'; ?>