<?php
require_once __DIR__.'/admin-auth.php';
$page_title = 'Orders';

/* ---------- Update order status (admin) ---------- */
if (isset($_GET['status'], $_GET['id'])) {
    $newStatus = $_GET['status'];
    $allowed   = ['received','preparing','ready','out_for_delivery','delivered','cancelled'];

    if (in_array($newStatus, $allowed, true)) {
        $pdo->prepare("UPDATE restaurant_orders SET status=? WHERE id=?")
            ->execute([$newStatus, (int)$_GET['id']]);
        flash('success', 'Order status updated to "' . $newStatus . '".');
    }
    redirect(BASE_URL . '/admin-orders.php');
}

/* ---------- Filters ---------- */
$filterStatus = $_GET['filter'] ?? '';
$search       = trim($_GET['q'] ?? '');

$sql    = "SELECT * FROM restaurant_orders WHERE 1=1";
$params = [];

if ($filterStatus !== '' && in_array($filterStatus, ['received','preparing','ready','out_for_delivery','delivered','cancelled'], true)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
}
if ($search !== '') {
    $sql .= " AND (order_ref LIKE ? OR customer_name LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC LIMIT 200";

$st = $pdo->prepare($sql);
$st->execute($params);
$orders = $st->fetchAll();

/* ---------- Quick stats ---------- */
$counts = [];
$rows = $pdo->query("SELECT status, COUNT(*) c FROM restaurant_orders GROUP BY status")->fetchAll();
foreach ($rows as $r) $counts[$r['status']] = (int)$r['c'];

require __DIR__.'/admin-header.php';
?>

<!-- ============ FILTER BAR ============ -->
<div class="admin-filter-bar">
  <form method="get" class="admin-filters">
    <input type="search" name="q" placeholder="Search by ref, name, or phone…"
           value="<?= e($search) ?>" aria-label="Search orders">

    <select name="filter" aria-label="Filter by status">
      <option value="">All statuses</option>
      <?php foreach (['received','preparing','ready','out_for_delivery','delivered','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>>
          <?= ucfirst(str_replace('_', ' ', $s)) ?>
          <?php if (!empty($counts[$s])): ?> (<?= $counts[$s] ?>)<?php endif; ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button class="btn btn-primary btn-sm">Apply</button>
    <?php if ($filterStatus || $search): ?>
      <a href="<?= BASE_URL ?>/admin-orders.php" class="btn btn-outline-dark btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <div class="admin-status-chips">
    <?php foreach (['received','preparing','ready','out_for_delivery','delivered','cancelled'] as $s):
      $c = $counts[$s] ?? 0;
      if ($c === 0) continue;
    ?>
      <a href="<?= BASE_URL ?>/admin-orders.php?filter=<?= $s ?>"
         class="chip <?= $filterStatus === $s ? 'active' : '' ?>">
        <?= ucfirst(str_replace('_', ' ', $s)) ?> · <?= $c ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- ============ ORDERS TABLE ============ -->
<?php if (!$orders): ?>
  <p class="empty-state">No orders found matching your filter.</p>
<?php else: ?>
  <div class="table-scroll">
    <table class="cart-table">
      <thead>
        <tr>
          <th>Ref</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <?php
            $itemCount = (int)$pdo->query("SELECT IFNULL(SUM(quantity),0) FROM restaurant_order_items WHERE order_id=" . (int)$o['id'])->fetchColumn();
            $isCancelled = $o['status'] === 'cancelled';
          ?>
          <tr class="<?= $isCancelled ? 'row-cancelled' : '' ?>">
            <td>
              <a href="<?= BASE_URL ?>/admin-order-view.php?id=<?= (int)$o['id'] ?>">
                <code><?= e($o['order_ref']) ?></code>
              </a>
            </td>
            <td>
              <strong><?= e($o['customer_name']) ?></strong><br>
              <small class="muted"><?= e($o['phone']) ?></small>
            </td>
            <td>
              <span class="badge-count"><?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?></span>
            </td>
            <td><strong><?= money($o['total']) ?></strong></td>
            <td>
              <span class="status status-<?= e($o['payment_status']) ?>">
                <?= e($o['payment_status']) ?>
              </span>
            </td>
            <td>
              <?php if ($isCancelled && !empty($o['cancel_reason'])): ?>
                <span class="status status-cancelled"
                      title="<?= e($o['cancel_reason']) ?>"
                      style="cursor:help">
                  Cancelled
                </span>
                <br>
                <small class="muted"><?= e(excerpt($o['cancel_reason'], 40)) ?></small>
              <?php else: ?>
                <select class="status-select"
                        onchange="location.href='<?= BASE_URL ?>/admin-orders.php?status='+this.value+'&id=<?= (int)$o['id'] ?><?= $filterStatus ? '&filter='.$filterStatus : '' ?><?= $search ? '&q='.urlencode($search) : '' ?>'">
                  <?php foreach (['received','preparing','ready','out_for_delivery','delivered','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>>
                      <?= ucfirst(str_replace('_', ' ', $s)) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
            </td>
            <td>
              <small class="muted">
                <?= date('d M', strtotime($o['created_at'])) ?><br>
                <?= date('H:i', strtotime($o['created_at'])) ?>
              </small>
            </td>
            <td class="row-actions">
              <a href="<?= BASE_URL ?>/admin-order-view.php?id=<?= (int)$o['id'] ?>"
                 class="btn btn-outline-dark btn-sm">
                View
              </a>
              <a href="<?= BASE_URL ?>/order-track.php?ref=<?= urlencode($o['order_ref']) ?>"
                 target="_blank"
                 class="btn btn-outline-dark btn-sm"
                 title="View tracking page">
                Track
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<!-- ============ EXTRA STYLES ============ -->
<style>
.admin-filter-bar{
  display:flex;flex-direction:column;gap:14px;margin-bottom:22px;
}
.admin-filters{
  display:flex;gap:10px;flex-wrap:wrap;align-items:center;
  background:#fff;padding:14px;border-radius:12px;
  border:1px solid #e8e8e8;
}
.admin-filters input[type=search],
.admin-filters select{
  margin:0;flex:1;min-width:180px;
}
.admin-status-chips{
  display:flex;gap:8px;flex-wrap:wrap;
}
.badge-count{
  display:inline-block;padding:3px 10px;background:#f0f0f0;
  border-radius:20px;font-size:.78rem;font-weight:600;color:#555;
}
.status-select{
  padding:6px 10px;border-radius:6px;border:1px solid #e0e0e0;
  background:#fff;font-size:.82rem;cursor:pointer;
  max-width:160px;
}
.status-select:focus{border-color:#e67e22;outline:none}
.row-cancelled td{opacity:.75}
.row-cancelled td code{text-decoration:line-through}
.row-actions{
  white-space:nowrap;
  display:flex;
  gap:6px;
}
.row-actions .btn{padding:5px 10px;font-size:.78rem}
.muted{color:#888}
</style>

<?php require __DIR__.'/admin-footer.php'; ?>