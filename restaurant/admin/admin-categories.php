<?php
require_once __DIR__.'/admin-auth.php';
require_once __DIR__.'/csrf.php';
$page_title = 'Categories';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (!empty($_POST['id'])) {
        $pdo->prepare("UPDATE restaurant_categories SET name=?, slug=?, sort_order=? WHERE id=?")
            ->execute([trim($_POST['name']), slugify($_POST['name']), (int)$_POST['sort_order'], (int)$_POST['id']]);
        flash('success', 'Category updated.');
    } else {
        $pdo->prepare("INSERT INTO restaurant_categories (name,slug,sort_order) VALUES (?,?,?)")
            ->execute([trim($_POST['name']), slugify($_POST['name']), (int)$_POST['sort_order']]);
        flash('success', 'Category added.');
    }
    redirect(BASE_URL.'/admin-categories.php');
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM restaurant_categories WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success', 'Category deleted.');
    redirect(BASE_URL.'/admin-categories.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM restaurant_categories WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}

$cats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM restaurant_menu_items m WHERE m.category_id=c.id) cnt
                     FROM restaurant_categories c ORDER BY c.sort_order")->fetchAll();

require __DIR__.'/admin-header.php';
?>

<form method="post" class="checkout-form" style="max-width:520px;margin-bottom:30px">
  <?= csrf_field() ?>
  <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
  <label>Name <input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required></label>
  <label>Sort Order <input type="number" name="sort_order" value="<?= e($edit['sort_order'] ?? 0) ?>"></label>
  <button class="btn btn-primary"><?= $edit ? 'Update' : 'Add' ?> Category</button>
  <?php if ($edit): ?><a href="<?= BASE_URL ?>/admin-categories.php" class="btn btn-outline-dark">Cancel</a><?php endif; ?>
</form>

<div class="table-scroll">
<table class="cart-table">
  <tr><th>#</th><th>Name</th><th>Slug</th><th>Dishes</th><th>Order</th><th>Actions</th></tr>
  <?php foreach ($cats as $c): ?>
    <tr>
      <td><?= (int)$c['id'] ?></td>
      <td><?= e($c['name']) ?></td>
      <td><code><?= e($c['slug']) ?></code></td>
      <td><?= (int)$c['cnt'] ?></td>
      <td><?= (int)$c['sort_order'] ?></td>
      <td>
        <a href="<?= BASE_URL ?>/admin-categories.php?edit=<?= (int)$c['id'] ?>">Edit</a> ·
        <a href="<?= BASE_URL ?>/admin-categories.php?delete=<?= (int)$c['id'] ?>">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
</div>

<?php require __DIR__.'/admin-footer.php'; ?>