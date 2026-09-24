<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$site_name  = setting($pdo, 'site_name', 'Restaurant');
$page_title = 'Menu | ' . $site_name;

/* ---------- Inputs ---------- */
$cat   = trim($_GET['cat']  ?? '');
$all   = !empty($_GET['all']);         // "All Dishes" card uses ?all=1
$q     = trim($_GET['q']    ?? '');
$veg   = !empty($_GET['veg']);
$spicy = !empty($_GET['spicy']);
$pop   = !empty($_GET['pop']);
$sort  = $_GET['sort'] ?? 'popular';

/* ---------- Load all categories ---------- */
$cats = safe_query($pdo, "SELECT id, name, slug, sort_order
                          FROM restaurant_categories
                          ORDER BY sort_order ASC, name ASC");

/* ---------- Resolve `cat` → category row ---------- */
$activeCat = null;
if ($cat !== '') {
    foreach ($cats as $c) {
        if ((string)$c['slug'] === $cat || (string)$c['id'] === $cat) {
            $activeCat = $c;
            break;
        }
    }
}

/* ---------- Build dish query ---------- */
$sql = "SELECT m.*, c.name AS cat_name, c.slug AS cat_slug
        FROM restaurant_menu_items m
        LEFT JOIN restaurant_categories c ON c.id = m.category_id
        WHERE m.available = 1";
$params = [];

/* Category filter (skipped when "all" is requested) */
if ($activeCat && !$all) {
    $sql .= " AND m.category_id = ?";
    $params[] = (int)$activeCat['id'];
}

/* Search */
if ($q !== '') {
    $sql .= " AND (m.name LIKE ? OR m.description LIKE ? OR m.ingredients LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}
if ($veg)   $sql .= " AND m.is_vegetarian = 1";
if ($spicy) $sql .= " AND m.is_spicy = 1";
if ($pop)   $sql .= " AND m.is_popular = 1";

/* Sort */
switch ($sort) {
    case 'price_asc':  $sql .= " ORDER BY m.price ASC";        break;
    case 'price_desc': $sql .= " ORDER BY m.price DESC";       break;
    case 'newest':     $sql .= " ORDER BY m.id DESC";          break;
    case 'name':       $sql .= " ORDER BY m.name ASC";         break;
    default:           $sql .= " ORDER BY m.is_popular DESC, m.name ASC";
}

$items = safe_query($pdo, $sql, $params);

/* Heading */
$heading = $activeCat && !$all ? $activeCat['name'] : 'Full Menu';
$subhead = $activeCat && !$all
    ? 'All our ' . strtolower($activeCat['name']) . ' dishes'
    : 'Explore every dish we serve';

require __DIR__.'/header.php';
?>

<section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1920&q=80')">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <p class="hero-eyebrow">Freshly prepared daily</p>
    <h1><?= e($heading) ?></h1>
    <p class="hero-sub"><?= e($subhead) ?></p>
  </div>
</section>

<!-- ============ CATEGORY CARDS ============ -->
<section class="container" style="padding-top:50px">
  <header class="section-head">
    <div>
      <p class="section-eyebrow">Browse by category</p>
      <h2 class="section-title">What are you craving?</h2>
    </div>
  </header>

  <div class="category-cards">

    <!-- "All Dishes" card — shows every dish, triggers scroll + fade-in -->
    <a href="<?= BASE_URL ?>/menu.php?all=1#results"
       class="cat-card <?= (!$activeCat && !$all) || $all ? 'is-active' : '' ?>">
      <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80"
           alt="All dishes" loading="lazy">
      <span class="cat-label">All Dishes</span>
    </a>

    <?php foreach ($cats as $c): ?>
      <a href="<?= BASE_URL ?>/menu.php?cat=<?= urlencode($c['slug']) ?>#results"
         class="cat-card <?= $activeCat && !$all && (int)$activeCat['id'] === (int)$c['id'] ? 'is-active' : '' ?>">
        <img src="<?= e(category_image($c['slug'])) ?>"
             alt="<?= e($c['name']) ?>" loading="lazy">
        <span class="cat-label"><?= e($c['name']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ============ FILTERS ============ -->
<section class="container">
  <form class="filters" method="get" role="search">
    <?php if ($activeCat && !$all): ?>
      <input type="hidden" name="cat" value="<?= e($activeCat['slug']) ?>">
    <?php endif; ?>

    <input type="search" name="q" placeholder="Search dishes…"
           value="<?= e($q) ?>" aria-label="Search dishes">

    <select name="sort" aria-label="Sort">
      <option value="popular"    <?= $sort==='popular'    ? 'selected':'' ?>>Most popular</option>
      <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected':'' ?>>Price low → high</option>
      <option value="price_desc" <?= $sort==='price_desc' ? 'selected':'' ?>>Price high → low</option>
      <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Newest</option>
      <option value="name"       <?= $sort==='name'       ? 'selected':'' ?>>Name A–Z</option>
    </select>

    <label class="check">
      <input type="checkbox" name="veg" value="1" <?= $veg ? 'checked' : '' ?>>
      <?= icon('leaf', 14) ?> Vegetarian
    </label>
    <label class="check">
      <input type="checkbox" name="spicy" value="1" <?= $spicy ? 'checked' : '' ?>>
      <?= icon('fire', 14) ?> Spicy
    </label>
    <label class="check">
      <input type="checkbox" name="pop" value="1" <?= $pop ? 'checked' : '' ?>>
      <?= icon('star', 14) ?> Popular
    </label>

    <button class="btn btn-primary">Apply</button>
    <?php if ($activeCat || $all || $q || $veg || $spicy || $pop): ?>
      <a href="<?= BASE_URL ?>/menu.php" class="btn btn-outline-dark">Reset</a>
    <?php endif; ?>
  </form>
</section>

<!-- ============ RESULTS ============ -->
<section class="container section" id="results"
         style="padding-top:20px;scroll-margin-top:90px">

  <?php if ($activeCat && !$all): ?>
    <p class="results-count">
      <strong><?= count($items) ?></strong>
      <?= count($items) === 1 ? 'dish' : 'dishes' ?>
      in <span class="accent"><?= e($activeCat['name']) ?></span>
    </p>
  <?php else: ?>
    <p class="results-count">
      <strong><?= count($items) ?></strong>
      <?= count($items) === 1 ? 'dish' : 'dishes' ?>
      available
    </p>
  <?php endif; ?>

  <?php if (!$items): ?>
    <p class="empty-state">
      No dishes match your filter.
      <a href="<?= BASE_URL ?>/menu.php">View full menu</a>
    </p>
  <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($items as $item): ?>
        <article class="menu-card">
          <div class="menu-card-img">
            <img src="<?= e(menu_image_url($item['image'])) ?>"
                 alt="<?= e($item['name']) ?>"
                 width="400" height="300" loading="lazy"
                 onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 400 300%22><rect fill=%22%23f4f4f4%22 width=%22400%22 height=%22300%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.35em%22 font-family=%22sans-serif%22 font-size=%2220%22 fill=%22%23bbb%22>No image</text></svg>';">
            <div class="menu-card-tags">
              <?php if (!empty($item['is_popular'])):    ?><span class="tag popular"><?= icon('star', 12) ?> Popular</span><?php endif; ?>
              <?php if (!empty($item['is_vegetarian'])): ?><span class="tag veg"><?= icon('leaf', 12) ?> Veg</span><?php endif; ?>
              <?php if (!empty($item['is_spicy'])):      ?><span class="tag spicy"><?= icon('fire', 12) ?> Spicy</span><?php endif; ?>
            </div>
            <?php if (!empty($item['cat_name'])): ?>
              <a class="menu-card-cat"
                 href="<?= BASE_URL ?>/menu.php?cat=<?= urlencode($item['cat_slug']) ?>#results">
                <?= e($item['cat_name']) ?>
              </a>
            <?php endif; ?>
          </div>

          <div class="menu-card-body">
            <h3><?= e($item['name']) ?></h3>
            <p><?= e(excerpt($item['description'], 100)) ?></p>
            <?php if (!empty($item['ingredients'])): ?>
              <small class="ingredients"><em><?= e($item['ingredients']) ?></em></small>
            <?php endif; ?>

            <div class="menu-card-footer">
              <span class="price"><?= money($item['price']) ?></span>
              <button type="button"
                      class="btn btn-primary btn-sm add-to-cart"
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

<?php require __DIR__.'/footer.php'; ?>