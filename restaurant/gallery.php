<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$page_title = 'Gallery | ' . setting($pdo, 'site_name', 'Restaurant');
$cat = $_GET['cat'] ?? 'all';

$sql = "SELECT * FROM restaurant_gallery";
$params = [];
if ($cat !== 'all') { $sql .= " WHERE category=?"; $params[] = $cat; }
$sql .= " ORDER BY created_at DESC";
$st = $pdo->prepare($sql);
$st->execute($params);
$images = $st->fetchAll();

/* Fallback online gallery when DB has no images */
$fallback = [
  ['food',     'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&q=80', 'Fresh Garden Salad'],
  ['food',     'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800&q=80', 'Wood-fired Pizza'],
  ['food',     'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&q=80', 'Signature Burger'],
  ['food',     'https://images.unsplash.com/photo-1544025162-d76694265947?w=800&q=80', 'Grilled Platter'],
  ['interior', 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&q=80', 'Main Dining Room'],
  ['interior', 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800&q=80', 'Warm Ambience'],
  ['interior', 'https://images.unsplash.com/photo-1552566626-52f8b828add9?w=800&q=80', 'Private Corner'],
  ['exterior', 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=800&q=80', 'Evening View'],
  ['events',   'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&q=80', 'Celebration Night'],
  ['drinks',   'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=800&q=80', 'Signature Cocktails'],
  ['drinks',   'https://images.unsplash.com/photo-1551024709-8f23befc6f87?w=800&q=80', 'Fresh Juice Bar'],
  ['food',     'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=800&q=80', 'Desserts'],
];
if (!$images) {
  $images = array_map(fn($f) => ['image' => $f[1], 'title' => $f[2], 'category' => $f[0], 'external' => true], $fallback);
  if ($cat !== 'all') {
    $images = array_values(array_filter($images, fn($i) => $i['category'] === $cat));
  }
}

require __DIR__.'/header.php';
?>

<section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1920&q=80')">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <p class="hero-eyebrow">Moments at our table</p>
    <h1>Gallery</h1>
    <p class="hero-sub">A look inside our kitchen, dining rooms and events</p>
  </div>
</section>

<section class="container section">
  <div class="category-chips">
    <?php foreach (['all','food','interior','exterior','events','drinks'] as $c): ?>
      <a href="?cat=<?= $c ?>" class="chip <?= $cat === $c ? 'active' : '' ?>"><?= ucfirst($c) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="gallery-grid">
    <?php foreach ($images as $img): ?>
      <?php
        $src = !empty($img['external']) ? $img['image'] : (UPLOAD_URL . e($img['image']));
      ?>
      <a href="<?= e($src) ?>" class="gallery-item" data-lightbox>
        <img src="<?= e($src) ?>" alt="<?= e($img['title'] ?? 'Gallery image') ?>" loading="lazy">
        <span class="gallery-caption"><?= e($img['title'] ?? '') ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$images): ?>
    <p class="empty-state">No images yet — check back soon!</p>
  <?php endif; ?>
</section>

<?php require __DIR__.'/footer.php'; ?>