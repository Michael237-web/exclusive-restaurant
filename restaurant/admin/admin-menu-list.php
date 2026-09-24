<?php
require_once __DIR__.'/admin-auth.php';
$page_title = 'Menu Items';

$items = $pdo->query("SELECT m.*, c.name cat FROM restaurant_menu_items m
                      LEFT JOIN restaurant_categories c ON c.id=m.category_id
                      ORDER BY m.created_at DESC")->fetchAll();

require __DIR__.'/admin-header.php';
?>

<div class="admin-actions">
  <a href="<?= BASE_URL ?>/admin-menu-form.php" class="btn btn-primary">
    <?= icon('menu', 14) ?> Add Dish
  </a>
</div>

<div class="table-scroll">
<table class="cart-table">
  <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Available</th><th>Actions</th></tr>
  <?php foreach ($items as $i): ?>
    <tr>
      <td>
        <img src="<?= e(menu_image_url($i['image'])) ?>"
             style="width:60px;height:60px;object-fit:cover;border-radius:8px"
             onerror="this.onerror=null;this.src='<?= UPLOAD_URL ?>menu/placeholder.jpg';">
      </td>
      <td><?= e($i['name']) ?></td>
      <td><?= e($i['cat']) ?></td>
      <td><?= money($i['price']) ?></td>
      <td><?= $i['available'] ? '✅' : '❌' ?></td>
      <td>
        <a href="<?= BASE_URL ?>/admin-menu-form.php?id=<?= (int)$i['id'] ?>">Edit</a> ·
        <a href="<?= BASE_URL ?>/admin-menu-form.php?delete=<?= (int)$i['id'] ?>">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
</div>

<?php require __DIR__.'/admin-footer.php'; ?>