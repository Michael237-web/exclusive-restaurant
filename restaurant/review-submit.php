<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';
require_once __DIR__.'/auth.php';

$user = current_user();
$page_title = 'Leave a Review | ' . setting($pdo, 'site_name', 'Restaurant');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name    = trim($_POST['name'] ?? '');
    $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');

    if ($name && $comment) {
        $st = $pdo->prepare("INSERT INTO restaurant_reviews (user_id,name,rating,comment,approved) VALUES (?,?,?,?,0)");
        $st->execute([$user['id'] ?? null, $name, $rating, $comment]);
        flash('review_ok', 'Thank you! Your review is pending approval.');
        redirect(BASE_URL.'/review-submit.php');
    }
}

require __DIR__.'/header.php';
?>
<section class="container section" style="max-width:520px">
  <h1>Leave a Review</h1>
  <?php if ($msg = flash('review_ok')): ?>
    <div class="alert success"><?= e($msg) ?></div>
  <?php endif; ?>

  <form method="post">
    <?= csrf_field() ?>
    <label>Your Name *<input type="text" name="name" required value="<?= e($user['name'] ?? '') ?>"></label>
    <label>Rating *
      <select name="rating" required>
        <option value="5">★★★★★ Excellent</option>
        <option value="4">★★★★ Good</option>
        <option value="3">★★★ Average</option>
        <option value="2">★★ Poor</option>
        <option value="1">★ Bad</option>
      </select>
    </label>
    <label>Your Review *<textarea name="comment" rows="5" required></textarea></label>
    <button class="btn btn-primary btn-lg">Submit Review</button>
  </form>
</section>
<?php require __DIR__.'/footer.php'; ?>