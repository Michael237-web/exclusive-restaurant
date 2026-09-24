<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$site_name  = setting($pdo, 'site_name', 'Restaurant');
$tagline    = setting($pdo, 'tagline', 'Authentic Kenyan & International Cuisine');
$page_title = $site_name . ' | ' . $tagline;
$page_desc  = setting($pdo, 'meta_description') ?: 'Authentic Kenyan & International cuisine in Nairobi. Order online, reserve a table, or visit one of our branches.';
$og_image   = setting($pdo, 'og_image') ?: UPLOAD_URL . 'menu/placeholder.jpg';

$featured = safe_query($pdo, "SELECT id,name,description,price,image FROM restaurant_menu_items WHERE is_popular=1 AND available=1 LIMIT 6");
$reviews  = safe_query($pdo, "SELECT name,rating,comment,created_at FROM restaurant_reviews WHERE approved=1 ORDER BY created_at DESC LIMIT 6");
$offers   = safe_query($pdo, "SELECT title,description,code,discount_percent FROM restaurant_promotions WHERE active=1 AND (expires_at IS NULL OR expires_at >= CURDATE()) LIMIT 3");
$branches = safe_query($pdo, "SELECT name,address,phone,hours FROM restaurant_branches WHERE active=1");

$heroSlides = [
  'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1920&q=80',
  'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1920&q=80',
  'https://images.unsplash.com/photo-1552566626-52f8b828add9?w=1920&q=80',
  'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1920&q=80',
];

require __DIR__.'/header.php';
?>

<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type'    => 'Restaurant',
  'name'     => $site_name,
  'servesCuisine' => ['Kenyan', 'International'],
  'priceRange' => '$$',
  'url'      => BASE_URL,
  'telephone'=> setting($pdo, 'phone'),
  'address'  => [
    '@type' => 'PostalAddress',
    'streetAddress' => 'Westlands',
    'addressLocality' => 'Nairobi',
    'addressCountry' => 'KE',
  ],
  'openingHours' => 'Mo-Su 07:00-23:00',
], JSON_UNESCAPED_SLASHES) ?>
</script>

<section class="hero" aria-label="Welcome">
  <div class="hero-slides" aria-hidden="true">
    <?php foreach ($heroSlides as $i => $src): ?>
      <div class="hero-slide <?= $i === 0 ? 'is-active' : '' ?>"
           style="background-image:url('<?= e($src) ?>')"></div>
    <?php endforeach; ?>
  </div>
  <div class="hero-overlay"></div>

  <div class="hero-content">
    <p class="hero-eyebrow"><?= e($site_name) ?></p>
    <h1>Taste the Difference</h1>
    <p class="hero-sub"><?= e($tagline) ?></p>
    <div class="hero-actions">
      <a href="<?= BASE_URL ?>/menu.php" class="btn btn-primary btn-lg"><?= icon('utensils', 18) ?> View Menu</a>
      <a href="<?= BASE_URL ?>/menu.php?order=1" class="btn btn-light btn-lg"><?= icon('cart', 18) ?> Order Online</a>
      <a href="<?= BASE_URL ?>/reserve.php" class="btn btn-ghost btn-lg"><?= icon('calendar', 18) ?> Reserve a Table</a>
    </div>
    <div class="hero-meta">
      <span><?= icon('clock') ?> Mon–Sun 7am–11pm</span>
      <span><?= icon('pin') ?> Westlands, Nairobi</span>
      <span><?= icon('star') ?> 4.9 · 500+ reviews</span>
    </div>
  </div>

  <div class="hero-thumbs" aria-hidden="true">
    <?php foreach ($heroSlides as $i => $src): ?>
      <button class="hero-thumb <?= $i === 0 ? 'is-active' : '' ?>"
              data-slide="<?= $i ?>"
              style="background-image:url('<?= e($src) ?>')"
              type="button"></button>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===== FEATURED ===== -->
<section class="container section" aria-labelledby="featured-h">
  <header class="section-head">
    <div>
      <p class="section-eyebrow">Chef's selection</p>
      <h2 id="featured-h" class="section-title">Featured Dishes</h2>
    </div>
    <a href="<?= BASE_URL ?>/menu.php" class="link-arrow">Full menu <?= icon('arrow') ?></a>
  </header>

  <?php if (!$featured): ?>
    <p class="empty-state">Our chefs are preparing something special. Check back soon.</p>
  <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($featured as $item): ?>
        <article class="menu-card">
          <a href="<?= BASE_URL ?>/menu.php?id=<?= (int)$item['id'] ?>" class="menu-card-img">
            <img src="<?= e(menu_image_url($item['image'])) ?>"
                 alt="<?= e($item['name']) ?>"
                 width="400" height="300"
                 loading="lazy" decoding="async"
                 onerror="this.onerror=null;this.src='<?= UPLOAD_URL ?>menu/placeholder.jpg';">
          </a>
          <div class="menu-card-body">
            <h3><?= e($item['name']) ?></h3>
            <p><?= e($item['description']) ?></p>
            <div class="menu-card-footer">
              <span class="price"><?= money($item['price']) ?></span>
              <button type="button" class="btn btn-primary btn-sm add-to-cart"
                      data-id="<?= (int)$item['id'] ?>"
                      aria-label="Add <?= e($item['name']) ?> to cart">
                <?= icon('cart', 16) ?> Add
              </button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- ===== OFFERS ===== -->
<?php if ($offers): ?>
<section class="section bg-amber" aria-labelledby="offers-h">
  <div class="container">
    <header class="section-head">
      <div>
        <p class="section-eyebrow">Limited time</p>
        <h2 id="offers-h" class="section-title">Special Offers</h2>
      </div>
    </header>
    <div class="grid grid-3">
      <?php foreach ($offers as $o): ?>
        <article class="offer-card">
          <?php if ($o['discount_percent']): ?>
            <span class="badge"><?= (int)$o['discount_percent'] ?>% OFF</span>
          <?php endif; ?>
          <h3><?= e($o['title']) ?></h3>
          <p><?= e($o['description']) ?></p>
          <?php if ($o['code']): ?>
            <button type="button" class="coupon" data-copy="<?= e($o['code']) ?>">
              <?= e($o['code']) ?> <span class="copy-hint">Copy</span>
            </button>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== REVIEWS ===== -->
<?php if ($reviews): ?>
<section class="container section" aria-labelledby="reviews-h">
  <header class="section-head">
    <div>
      <p class="section-eyebrow">Real guests</p>
      <h2 id="reviews-h" class="section-title">What Our Customers Say</h2>
    </div>
  </header>
  <div class="grid grid-3">
    <?php foreach ($reviews as $r): ?>
      <blockquote class="review-card">
        <div class="stars" aria-label="<?= (int)$r['rating'] ?> out of 5 stars">
          <?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?>
        </div>
        <p>“<?= e($r['comment']) ?>”</p>
        <footer class="review-meta">
          <strong><?= e($r['name']) ?></strong>
          <time datetime="<?= date('Y-m-d', strtotime($r['created_at'])) ?>">
            <?= date('M Y', strtotime($r['created_at'])) ?>
          </time>
        </footer>
      </blockquote>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ===== BRANCHES ===== -->
<?php if ($branches): ?>
<section class="section bg-dark" aria-labelledby="branches-h">
  <div class="container">
    <header class="section-head">
      <div>
        <p class="section-eyebrow">Find us</p>
        <h2 id="branches-h" class="section-title light">Our Branches</h2>
      </div>
    </header>
    <div class="grid grid-4">
      <?php foreach ($branches as $b): ?>
        <address class="branch-card">
          <h3><?= e($b['name']) ?></h3>
          <p><?= icon('pin') ?> <?= e($b['address']) ?></p>
          <p><?= icon('phone') ?> <a href="tel:<?= e(preg_replace('/\s+/', '', $b['phone'])) ?>"><?= e($b['phone']) ?></a></p>
          <p><?= icon('clock') ?> <?= e($b['hours']) ?></p>
        </address>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<script>
(function(){
  const slides = document.querySelectorAll('.hero-slide');
  const thumbs = document.querySelectorAll('.hero-thumb');
  if(!slides.length) return;
  let i = 0;
  const show = (n) => {
    slides.forEach((s, k) => s.classList.toggle('is-active', k === n));
    thumbs.forEach((t, k) => t.classList.toggle('is-active', k === n));
    i = n;
  };
  let timer = setInterval(() => show((i + 1) % slides.length), 5000);
  thumbs.forEach(t => t.addEventListener('click', () => {
    clearInterval(timer);
    show(parseInt(t.dataset.slide, 10));
    timer = setInterval(() => show((i + 1) % slides.length), 5000);
  }));
})();
</script>

<?php require __DIR__.'/footer.php'; ?>