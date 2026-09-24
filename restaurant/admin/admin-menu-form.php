<?php
require_once __DIR__.'/admin-auth.php';
require_once __DIR__.'/csrf.php';
$page_title = 'Edit Dish';

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM restaurant_menu_items WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success', 'Dish deleted.');
    redirect(BASE_URL.'/admin-menu-list.php');
}

$id = (int)($_GET['id'] ?? 0);
$item = ['name'=>'','description'=>'','ingredients'=>'','price'=>'','image'=>'','category_id'=>'','available'=>1,'is_vegetarian'=>0,'is_spicy'=>0,'is_popular'=>0];

if ($id) {
    $st = $pdo->prepare("SELECT * FROM restaurant_menu_items WHERE id=?");
    $st->execute([$id]);
    $item = $st->fetch() ?: $item;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $img = $item['image'];
    if (!empty($_FILES['image']['name'])) {
        $up = upload_image($_FILES['image'], 'menu');
        if ($up) $img = $up;
    }
    $data = [
        trim($_POST['name']),
        slugify($_POST['name']),
        trim($_POST['description']),
        trim($_POST['ingredients']),
        (int)$_POST['price'],
        $img,
        (int)$_POST['category_id'],
        !empty($_POST['available']) ? 1 : 0,
        !empty($_POST['is_vegetarian']) ? 1 : 0,
        !empty($_POST['is_spicy']) ? 1 : 0,
        !empty($_POST['is_popular']) ? 1 : 0,
    ];

    if ($id) {
        $data[] = $id;
        $pdo->prepare("UPDATE restaurant_menu_items SET name=?,slug=?,description=?,ingredients=?,price=?,image=?,category_id=?,available=?,is_vegetarian=?,is_spicy=?,is_popular=? WHERE id=?")
            ->execute($data);
        flash('success', 'Dish updated.');
    } else {
        $pdo->prepare("INSERT INTO restaurant_menu_items (name,slug,description,ingredients,price,image,category_id,available,is_vegetarian,is_spicy,is_popular) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute($data);
        flash('success', 'Dish added.');
    }
    redirect(BASE_URL.'/admin-menu-list.php');
}

$cats = $pdo->query("SELECT * FROM restaurant_categories ORDER BY name")->fetchAll();

require __DIR__.'/admin-header.php';
?>

<form method="post" enctype="multipart/form-data" class="checkout-form" style="max-width:600px">
  <?= csrf_field() ?>
  <label>Name <input type="text" name="name" value="<?= e($item['name']) ?>" required></label>
  <label>Description <textarea name="description" rows="3"><?= e($item['description']) ?></textarea></label>
  <label>Ingredients <textarea name="ingredients" rows="2"><?= e($item['ingredients']) ?></textarea></label>
  <label>Price (KSh) <input type="number" name="price" value="<?= e($item['price']) ?>" required></label>
  <label>Category
    <select name="category_id">
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $item['category_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Image <input type="file" name="image" accept="image/*"></label>
  <?php if ($item['image']): ?>
    <img src="<?= UPLOAD_URL . e($item['image']) ?>" style="max-width:140px;border-radius:8px;margin-bottom:12px">
  <?php endif; ?>
  <div class="radio-row vertical" style="margin:12px 0">
    <label><input type="checkbox" name="available"     <?= $item['available'] ? 'checked' : '' ?>> Available</label>
    <label><input type="checkbox" name="is_vegetarian" <?= $item['is_vegetarian'] ? 'checked' : '' ?>> Vegetarian</label>
    <label><input type="checkbox" name="is_spicy"      <?= $item['is_spicy'] ? 'checked' : '' ?>> Spicy</label>
    <label><input type="checkbox" name="is_popular"    <?= $item['is_popular'] ? 'checked' : '' ?>> Popular</label>
  </div>
  <button class="btn btn-primary">Save</button>
  <a href="<?= BASE_URL ?>/admin-menu-list.php" class="btn btn-outline-dark">Cancel</a>
</form>

<?php require __DIR__.'/admin-footer.php'; ?>