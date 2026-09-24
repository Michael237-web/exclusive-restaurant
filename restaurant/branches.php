<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$page_title = 'Our Branches | ' . setting($pdo, 'site_name', 'Restaurant');

/* Pull active branches from DB */
$branches = [];
try {
    $branches = $pdo->query("SELECT * FROM restaurant_branches WHERE active=1 ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    error_log('branches query failed: '.$e->getMessage());
}

/* If DB has no branches, use these five professional defaults */
if (!$branches) {
    $branches = [
        [
            'name'    => 'Nairobi CBD',
            'address' => 'Kenyatta Avenue, Nairobi CBD, Nairobi',
            'phone'   => '+254 700 111 001',
            'email'   => 'cbd@restaurant.co.ke',
            'hours'   => 'Mon–Sun · 7:00 AM – 11:00 PM',
            'image'   => 'https://images.unsplash.com/photo-1611348586804-61bf6c080437?w=1200&q=80',
            'tagline' => 'Our flagship branch in the heart of the city',
        ],
        [
            'name'    => 'Thika Road',
            'address' => 'Garden City Mall, Thika Superhighway, Nairobi',
            'phone'   => '+254 700 111 002',
            'email'   => 'thikaroad@restaurant.co.ke',
            'hours'   => 'Mon–Sun · 7:00 AM – 11:00 PM',
            'image'   => 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=1200&q=80',
            'tagline' => 'Convenient stop along the Thika Superhighway',
        ],
        [
            'name'    => 'Nakuru',
            'address' => 'Kenyatta Avenue, Nakuru Town, Nakuru',
            'phone'   => '+254 700 111 003',
            'email'   => 'nakuru@restaurant.co.ke',
            'hours'   => 'Mon–Sun · 7:00 AM – 10:30 PM',
            'image'   => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&q=80',
            'tagline' => 'Serving the Rift Valley with pride',
        ],
        [
            'name'    => 'Eldoret',
            'address' => 'Uganda Road, Eldoret Town, Uasin Gishu',
            'phone'   => '+254 700 111 004',
            'email'   => 'eldoret@restaurant.co.ke',
            'hours'   => 'Mon–Sun · 7:00 AM – 10:30 PM',
            'image'   => 'https://images.unsplash.com/photo-1552566626-52f8b828add9?w=1200&q=80',
            'tagline' => 'Home of champions — and great food',
        ],
        [
            'name'    => 'Karen',
            'address' => 'Karen Road, Karen, Nairobi',
            'phone'   => '+254 700 111 005',
            'email'   => 'karen@restaurant.co.ke',
            'hours'   => 'Mon–Sun · 8:00 AM – 11:00 PM',
            'image'   => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1200&q=80',
            'tagline' => 'Garden dining in Nairobi\'s leafy suburb',
        ],
    ];
}

require __DIR__.'/header.php';
?>

<section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1552566626-52f8b828add9?w=1920&q=80')">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <p class="hero-eyebrow">Find your nearest table</p>
    <h1>Our Branches</h1>
    <p class="hero-sub">Five locations across Kenya — same warm welcome, same great food</p>
  </div>
</section>

<!-- ===== BRANCH CARDS ===== -->
<section class="container section">
  <header class="section-head">
    <div>
      <p class="section-eyebrow">Visit us</p>
      <h2 class="section-title">Choose your branch</h2>
    </div>
  </header>

  <div class="branches-list">
    <?php foreach ($branches as $i => $b):
      $img = !empty($b['image'])
        ? (str_starts_with($b['image'], 'http') ? $b['image'] : UPLOAD_URL . e($b['image']))
        : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&q=80';

      $phoneRaw = preg_replace('/\D/', '', $b['phone'] ?? '');
      $waPhone  = $phoneRaw ? 'https://wa.me/'.$phoneRaw : '#';
    ?>
      <article class="branch-row <?= $i % 2 === 1 ? 'is-reversed' : '' ?>">
        <div class="branch-photo">
          <img src="<?= e($img) ?>" alt="<?= e($b['name']) ?>" loading="lazy" width="800" height="600">
          <span class="branch-num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
        </div>

        <div class="branch-info">
          <?php if (!empty($b['tagline'])): ?>
            <p class="section-eyebrow"><?= e($b['tagline']) ?></p>
          <?php endif; ?>
          <h3><?= e($b['name']) ?></h3>

          <ul class="branch-meta">
            <li><?= icon('pin', 16) ?> <span><?= e($b['address'] ?? '') ?></span></li>
            <?php if (!empty($b['phone'])): ?>
              <li><?= icon('phone', 16) ?>
                <a href="tel:<?= e($phoneRaw) ?>"><?= e($b['phone']) ?></a>
              </li>
            <?php endif; ?>
            <?php if (!empty($b['email'])): ?>
              <li><?= icon('mail', 16) ?>
                <a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a>
              </li>
            <?php endif; ?>
            <?php if (!empty($b['hours'])): ?>
              <li><?= icon('clock', 16) ?> <span><?= e($b['hours']) ?></span></li>
            <?php endif; ?>
          </ul>

          <div class="branch-actions">
            <a href="<?= BASE_URL ?>/reserve.php?branch=<?= urlencode($b['name']) ?>"
               class="btn btn-primary btn-sm">
              <?= icon('calendar', 14) ?> Reserve
            </a>
            <a href="tel:<?= e($phoneRaw) ?>" class="btn btn-outline-dark btn-sm">
              <?= icon('phone', 14) ?> Call
            </a>
            <a href="<?= e($waPhone) ?>" target="_blank" rel="noopener"
               class="btn btn-whatsapp btn-sm">
              <?= icon('whatsapp', 14) ?> WhatsApp
            </a>
            <a href="https://www.google.com/maps/search/<?= urlencode($b['address'] ?? $b['name']) ?>"
               target="_blank" rel="noopener"
               class="btn btn-outline-dark btn-sm">
              <?= icon('pin', 14) ?> Directions
            </a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===== MAPS ===== -->
<section class="section bg-amber">
  <div class="container">
    <header class="section-head">
      <div>
        <p class="section-eyebrow">On the map</p>
        <h2 class="section-title">Where you'll find us</h2>
      </div>
    </header>

    <div class="grid grid-2">
      <?php foreach ($branches as $b): ?>
        <div class="branch-map-card">
          <iframe
            src="https://www.google.com/maps?q=<?= urlencode(($b['name'] ?? '').', '.($b['address'] ?? 'Kenya')) ?>&output=embed"
            width="100%" height="260"
            style="border:0" loading="lazy"
            title="Map of <?= e($b['name']) ?>"></iframe>
          <div class="branch-map-info">
            <strong><?= e($b['name']) ?></strong>
            <span><?= e($b['address'] ?? '') ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== CTA ===== -->
<section class="section cta-section">
  <div class="container" style="text-align:center">
    <h2 class="section-title">Can't decide? Let us help</h2>
    <p style="color:var(--ink-soft);margin-bottom:24px">
      Tell us where you are and we'll recommend the closest branch.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>/contact.php" class="btn btn-primary btn-lg">
        <?= icon('mail', 16) ?> Contact us
      </a>
      <a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting($pdo, 'whatsapp', '254700000000'))) ?>"
         target="_blank" rel="noopener" class="btn btn-whatsapp btn-lg">
        <?= icon('whatsapp', 16) ?> WhatsApp
      </a>
    </div>
  </div>
</section>

<?php require __DIR__.'/footer.php'; ?>