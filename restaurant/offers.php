<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$page_title = 'Special Offers | ' . setting($pdo, 'site_name', 'Restaurant');

$offers = safe_query($pdo,
    "SELECT p.*,
            m.name       AS dish_name,
            m.price      AS dish_price,
            m.image      AS dish_image,
            m.available  AS dish_available
     FROM restaurant_promotions p
     LEFT JOIN restaurant_menu_items m ON m.id = p.menu_item_id
     WHERE p.active = 1
       AND (p.expires_at IS NULL OR p.expires_at >= CURDATE())
     ORDER BY p.id DESC"
);

require __DIR__.'/header.php';
?>

<section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1607083206869-4c7672e72a8a?w=1920&q=80')">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <p class="hero-eyebrow">Deals worth sharing</p>
    <h1>Special Offers</h1>
    <p class="hero-sub">Grab these deals before they expire</p>
  </div>
</section>

<section class="container section">
  <?php if (!$offers): ?>
    <p class="empty-state">No active offers right now. Check back soon!</p>
  <?php else: ?>
    <p class="results-count">
      <strong><?= count($offers) ?></strong>
      <?= count($offers) === 1 ? 'offer' : 'offers' ?> available now
    </p>

    <div class="grid grid-3">
      <?php foreach ($offers as $o): ?>
        <article class="offer-card">

          <?php if (!empty($o['image'])): ?>
            <img src="<?= e(menu_image_url($o['image'])) ?>"
                 alt="<?= e($o['title']) ?>"
                 class="offer-img" loading="lazy"
                 onerror="this.onerror=null;this.src='<?= UPLOAD_URL ?>menu/placeholder.jpg';">
          <?php endif; ?>

          <?php if (!empty($o['discount_percent'])): ?>
            <div class="badge"><?= (int)$o['discount_percent'] ?>% OFF</div>
          <?php endif; ?>

          <h3><?= e($o['title']) ?></h3>
          <p><?= e($o['description']) ?></p>

          <?php if (!empty($o['dish_name'])): ?>
            <p class="offer-dish">
              <strong>Featuring:</strong> <?= e($o['dish_name']) ?>
              · <?= money($o['dish_price']) ?>
            </p>
          <?php endif; ?>

          <?php if (!empty($o['code'])): ?>
            <button type="button" class="coupon" data-copy="<?= e($o['code']) ?>">
              <?= e($o['code']) ?> <span class="copy-hint">Copy</span>
            </button>
          <?php endif; ?>

          <?php if (!empty($o['expires_at'])): ?>
            <p class="offer-expiry">
              <?= icon('clock', 14) ?> Valid until <?= date('d M Y', strtotime($o['expires_at'])) ?>
            </p>
          <?php endif; ?>

          <?php if (!empty($o['menu_item_id']) && !empty($o['dish_available'])): ?>
            <button type="button"
                    class="btn btn-primary btn-sm add-to-cart"
                    data-id="<?= (int)$o['menu_item_id'] ?>"
                    aria-label="Add <?= e($o['dish_name']) ?> to cart">
              <?= icon('cart', 14) ?> Order Now
            </button>
          <?php else: ?>
            <a href="<?= BASE_URL ?>/menu.php" class="btn btn-primary btn-sm">
              <?= icon('utensils', 14) ?> Browse Menu
            </a>
          <?php endif; ?>

        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__.'/footer.php'; ?>