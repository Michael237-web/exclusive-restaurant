<?php
require_once __DIR__.'/admin-auth.php';
$page_title = 'Reviews';

if (isset($_GET['approve'])) {
    $pdo->prepare("UPDATE restaurant_reviews SET approved=1 WHERE id=?")->execute([(int)$_GET['approve']]);
    flash('success', 'Review approved.');
    redirect(BASE_URL.'/admin-reviews.php');
}
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM restaurant_reviews WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success', 'Review deleted.');
    redirect(BASE_URL.'/admin-reviews.php');
}

$rows = $pdo->query("SELECT * FROM restaurant_reviews ORDER BY created_at DESC")->fetchAll();
require __DIR__.'/admin-header.php';
?>

<div class="table-scroll">
<table class="cart-table">
  <tr><th>Name</th><th>Rating</th><th>Comment</th><th>Status</th><th>Actions</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['name']) ?></td>
      <td><?= str_repeat('★', (int)$r['rating']) ?></td>
      <td><?= e(excerpt($r['comment'], 100)) ?></td>
      <td><span class="status <?= $r['approved'] ? 'status-paid' : 'status-pending' ?>"><?= $r['approved'] ? 'Approved' : 'Pending' ?></span></td>
      <td>
        <?php if (!$r['approved']): ?><a href="<?= BASE_URL ?>/admin-reviews.php?approve=<?= (int)$r['id'] ?>">Approve</a> ·<?php endif; ?>
        <a href="<?= BASE_URL ?>/admin-reviews.php?delete=<?= (int)$r['id'] ?>">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
</div>

<?php require __DIR__.'/admin-footer.php'; ?>