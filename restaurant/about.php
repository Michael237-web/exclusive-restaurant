<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$page_title = 'About Us | ' . setting($pdo, 'site_name', 'Restaurant');
require __DIR__.'/header.php';
?>

<section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1600891964092-4316c288032e?w=1920&q=80')">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <p class="hero-eyebrow">Since 2015</p>
    <h1>Our Story</h1>
    <p class="hero-sub">A journey of flavor, family, and passion</p>
  </div>
</section>

<section class="container section">
  <div class="grid grid-2 split" >
    <div class="split-text">
      <p class="section-eyebrow">Our beginning</p>
      <h2>From a Small Kitchen to Nairobi's Favourite</h2>
      <p>Founded in 2015, <?= e(setting($pdo, 'site_name', 'our restaurant')) ?> began as a small family kitchen serving authentic Kenyan dishes to neighbours. Today we operate multiple branches across Nairobi, but our mission remains the same — to serve fresh, honest food that brings people together.</p>
      <p>We combine traditional Kenyan recipes passed down through generations with international techniques and the finest locally-sourced ingredients. Every dish is prepared fresh daily by our team of passionate chefs.</p>
      <div class="stats">
        <div><strong>10+</strong><span>Years serving</span></div>
        <div><strong>4</strong><span>Branches</span></div>
        <div><strong>50k+</strong><span>Happy guests</span></div>
      </div>
    </div>
    <img src="https://images.unsplash.com/photo-1552566626-52f8b828add9?w=1000&q=80"
         alt="Our kitchen" class="split-img" loading="lazy">
  </div>
</section>

<section class="section bg-amber">
  <div class="container">
    <header class="section-head">
      <div>
        <p class="section-eyebrow">Milestones</p>
        <h2 class="section-title">Our Journey</h2>
      </div>
    </header>
    <div class="timeline">
      <?php
      $milestones = [
        ['2015', 'Opened first location in Westlands with just 8 tables'],
        ['2017', 'Expanded to Kilimani — introduced our famous Chicken Burger'],
        ['2019', 'Launched online ordering and delivery across Nairobi'],
        ['2021', 'Opened Karen branch with outdoor garden seating'],
        ['2023', 'Won "Best Kenyan Cuisine" at Nairobi Food Awards'],
        ['2025', 'Now serving 4 branches and thousands of happy customers'],
      ];
      foreach ($milestones as $m): ?>
        <div class="timeline-item">
          <div class="timeline-year"><?= e($m[0]) ?></div>
          <div class="timeline-content"><?= e($m[1]) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="container section">
  <header class="section-head">
    <div>
      <p class="section-eyebrow">The team</p>
      <h2 class="section-title">Meet Our Chefs</h2>
    </div>
  </header>
  <div class="grid grid-3">
    <?php
    $chefs = [
      ['Chef Wanjiru', 'Head Chef', 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=600&q=80'],
      ['Chef Omondi',  'Kenyan Cuisine Specialist', 'https://images.unsplash.com/photo-1577219491135-ce391730fb2c?w=600&q=80'],
      ['Chef Aisha',   'Pastry Chef', 'https://images.unsplash.com/photo-1595273670150-bd0c3c392e46?w=600&q=80'],
    ];
    foreach ($chefs as $c): ?>
      <div class="chef-card">
        <img src="<?= e($c[2]) ?>" alt="<?= e($c[0]) ?>" loading="lazy">
        <h3><?= e($c[0]) ?></h3>
        <p><?= e($c[1]) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php require __DIR__.'/footer.php'; ?>