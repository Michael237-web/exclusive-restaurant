<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$ref = $_GET['ref'] ?? '';
$st  = $pdo->prepare("SELECT * FROM restaurant_orders WHERE order_ref=?");
$st->execute([$ref]);
$order = $st->fetch();

if (!$order) {
    /* Graceful fallback instead of a hard die() */
    $page_title = 'Order not found';
    require __DIR__.'/header.php';
    ?>
    <section class="container section" style="text-align:center;max-width:520px">
      <div class="confirm-icon" style="background:#fdecea;color:#b00020">
        <?= icon('close', 32) ?>
      </div>
      <h1>Order not found</h1>
      <p class="muted">We couldn't find an order with that reference.</p>
      <a href="<?= BASE_URL ?>/menu.php" class="btn btn-primary btn-lg" style="margin-top:20px">
        Back to menu
      </a>
    </section>
    <?php
    require __DIR__.'/footer.php';
    exit;
}

/* Load order items */
$items = $pdo->prepare("SELECT * FROM restaurant_order_items WHERE order_id=?");
$items->execute([$order['id']]);
$items = $items->fetchAll();

$paymentMethod = strtoupper($order['payment_method'] ?? 'cash');
$paymentStatus = $order['payment_status'] ?? 'pending';
$orderType     = ucfirst($order['order_type'] ?? 'delivery');

$page_title = 'Order Confirmed · ' . $order['order_ref'];
require __DIR__.'/header.php';
?>

<section class="container section order-success-page">

  <!-- ============ SUCCESS HEADER ============ -->
  <div class="success-header">
    <div class="confirm-icon">
      <?= icon('check', 34) ?>
    </div>
    <p class="section-eyebrow">Thank you<?= $order['customer_name'] ? ', ' . e(explode(' ', $order['customer_name'])[0]) : '' ?></p>
    <h1>Order Confirmed</h1>
    <p class="muted">
      We've received your order and started preparing it.
      <?php if ($paymentMethod === 'MPESA'): ?>
        Please complete payment on your phone if you haven't already.
      <?php endif; ?>
    </p>

    <div class="success-ref">
      <span>Order reference</span>
      <strong><?= e($order['order_ref']) ?></strong>
    </div>

    <div class="success-cta">
      <a href="<?= BASE_URL ?>/order-track.php?ref=<?= urlencode($order['order_ref']) ?>"
         class="btn btn-primary btn-lg">
        <?= icon('truck', 18) ?> Track your order
      </a>
      <a href="<?= BASE_URL ?>/menu.php" class="btn btn-outline-dark btn-lg">
        <?= icon('utensils', 18) ?> Order more
      </a>
    </div>
  </div>

  <!-- ============ QUICK INFO CARDS ============ -->
  <div class="grid grid-3 success-info">
    <div class="info-card">
      <div class="info-icon"><?= icon('clock', 20) ?></div>
      <small>Estimated time</small>
      <strong><?= $orderType === 'Pickup' ? '15–25 min' : '30–45 min' ?></strong>
    </div>

    <div class="info-card">
      <div class="info-icon"><?= icon('truck', 20) ?></div>
      <small>Order type</small>
      <strong><?= e($orderType) ?></strong>
    </div>

    <div class="info-card">
      <div class="info-icon"><?= icon('check', 20) ?></div>
      <small>Payment</small>
      <strong>
        <?= e(ucfirst($paymentMethod)) ?>
        <span class="status status-<?= e($paymentStatus) ?>" style="margin-left:6px">
          <?= e($paymentStatus) ?>
        </span>
      </strong>
    </div>
  </div>

  <!-- ============ ORDER SUMMARY ============ -->
  <div class="grid grid-2 success-summary" style="gap:24px;margin-top:40px">

    <div class="summary-box">
      <h2 class="cart-section-title">Delivery details</h2>
      <p><strong><?= e($order['customer_name']) ?></strong></p>
      <p class="muted"><?= e($order['phone']) ?></p>
      <?php if (!empty($order['email'])): ?>
        <p class="muted"><?= e($order['email']) ?></p>
      <?php endif; ?>
      <p class="muted">
        <?= e($order['address'] ?: 'Pickup at branch') ?>
      </p>
      <?php if (!empty($order['notes'])): ?>
        <div class="notes-box">
          <small><strong>Notes:</strong> <?= e($order['notes']) ?></small>
        </div>
      <?php endif; ?>
    </div>

    <div class="summary-box">
      <h2 class="cart-section-title">Your items (<?= count($items) ?>)</h2>
      <ul class="success-items">
        <?php foreach ($items as $it): ?>
          <li>
            <span><?= e($it['name']) ?> <small class="muted">× <?= (int)$it['quantity'] ?></small></span>
            <span><?= money($it['price'] * $it['quantity']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>

      <div class="success-totals">
        <div><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
        <div><span>Delivery</span><span><?= money($order['delivery_fee']) ?></span></div>
        <div class="grand"><span>Total</span><span><?= money($order['total']) ?></span></div>
      </div>
    </div>
  </div>

  <!-- ============ WHAT'S NEXT ============ -->
  <div class="next-steps">
    <h2 class="cart-section-title">What happens next?</h2>
    <ol class="steps-list">
      <li>
        <span class="step-num">1</span>
        <div>
          <strong>We confirm your order</strong>
          <p class="muted">Our team reviews and starts preparing your food.</p>
        </div>
      </li>
      <li>
        <span class="step-num">2</span>
        <div>
          <strong>We prepare your meal</strong>
          <p class="muted">Fresh ingredients, expert chefs, right on schedule.</p>
        </div>
      </li>
      <li>
        <span class="step-num">3</span>
        <div>
          <strong><?= $orderType === 'Pickup' ? 'Ready for pickup' : 'Out for delivery' ?></strong>
          <p class="muted">
            <?= $orderType === 'Pickup'
              ? 'You\'ll get a call when it\'s ready.'
              : 'Track the rider in real time from your tracking page.' ?>
          </p>
        </div>
      </li>
      <li>
        <span class="step-num">4</span>
        <div>
          <strong>Enjoy your meal</strong>
          <p class="muted">Bon appétit! Don't forget to rate us.</p>
        </div>
      </li>
    </ol>
  </div>

  <!-- ============ HELP ============ -->
  <div class="help-box">
    <p><strong>Need help?</strong> Call us at
      <a href="tel:<?= e(preg_replace('/\s+/', '', setting($pdo, 'phone', '+254700000000'))) ?>">
        <?= e(setting($pdo, 'phone', '+254 700 000 000')) ?>
      </a>
      or <a href="<?= BASE_URL ?>/contact.php">send a message</a>.
    </p>
  </div>
</section>

<!-- ============ EXTRA STYLES ============ -->
<style>
.order-success-page{max-width:960px}

.success-header{text-align:center;margin-bottom:40px}
.success-header .confirm-icon{
  width:80px;height:80px;margin:0 auto 22px;
  background:var(--brand);color:#fff;border-radius:50%;
  display:grid;place-items:center;
  box-shadow:0 16px 40px rgba(230,126,34,.4);
  animation:popIn .5s cubic-bezier(.22,.61,.36,1);
}
.success-header h1{
  font-size:clamp(1.8rem,3.5vw,2.6rem);
  letter-spacing:-.02em;margin:6px 0 10px;
}
.success-header .muted{color:var(--muted);max-width:520px;margin:0 auto}

.success-ref{
  display:inline-flex;flex-direction:column;gap:4px;
  margin:24px auto;padding:14px 26px;
  background:var(--brand-tint);border-radius:12px;
  border:1px dashed var(--brand);
}
.success-ref span{font-size:.75rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.success-ref strong{font-size:1.15rem;color:var(--brand-dark);letter-spacing:.02em;font-family:ui-monospace,monospace}

.success-cta{
  display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:20px;
}

.success-info{margin-top:20px}
.info-card{
  background:#fff;border:1px solid var(--line);border-radius:var(--radius);
  padding:20px;text-align:center;
  transition:transform .25s,box-shadow .25s;
}
.info-card:hover{transform:translateY(-3px);box-shadow:var(--shadow)}
.info-card .info-icon{
  width:44px;height:44px;border-radius:50%;
  background:var(--brand-tint);color:var(--brand);
  display:grid;place-items:center;margin:0 auto 12px;
}
.info-card small{
  display:block;font-size:.72rem;letter-spacing:.08em;
  text-transform:uppercase;color:var(--muted);margin-bottom:4px;
}
.info-card strong{font-size:1rem;color:var(--ink);display:flex;align-items:center;justify-content:center;gap:4px}

.summary-box{
  background:#fff;border:1px solid var(--line);
  border-radius:var(--radius);padding:24px;
}
.summary-box p{margin:6px 0;font-size:.94rem}

.notes-box{
  margin-top:14px;padding:10px 14px;
  background:var(--brand-tint);border-radius:8px;
  border-left:3px solid var(--brand);
}

.success-items{list-style:none;padding:0;margin:10px 0 16px}
.success-items li{
  display:flex;justify-content:space-between;
  padding:8px 0;font-size:.92rem;
  border-bottom:1px dashed var(--line);
}
.success-items li:last-child{border-bottom:0}

.success-totals{
  border-top:1px solid var(--line);padding-top:14px;margin-top:14px;
}
.success-totals div{
  display:flex;justify-content:space-between;padding:4px 0;
  font-size:.92rem;color:var(--ink-soft);
}
.success-totals .grand{
  border-top:1px solid var(--line);
  margin-top:10px;padding-top:12px;
  font-size:1.1rem;font-weight:800;color:var(--ink);
}

.next-steps{
  margin-top:48px;padding:28px;
  background:#fff;border:1px solid var(--line);
  border-radius:var(--radius);
}
.steps-list{list-style:none;padding:0;margin:16px 0 0;display:flex;flex-direction:column;gap:18px}
.steps-list li{display:flex;gap:16px;align-items:flex-start}
.step-num{
  flex-shrink:0;width:34px;height:34px;border-radius:50%;
  background:var(--brand);color:#fff;
  display:grid;place-items:center;font-weight:700;font-size:.9rem;
}
.steps-list strong{display:block;margin-bottom:2px;letter-spacing:-.01em}
.steps-list p{margin:0;font-size:.9rem}

.help-box{
  margin-top:32px;padding:20px;text-align:center;
  background:var(--bg-alt);border-radius:var(--radius);
  font-size:.92rem;
}
.help-box a{color:var(--brand);font-weight:600}

@keyframes popIn{from{transform:scale(.4);opacity:0}to{transform:scale(1);opacity:1}}

/* Dark mode */
body.dark .info-card,
body.dark .summary-box,
body.dark .next-steps{background:#1a1d22;border-color:#262b32}
body.dark .info-card strong{color:#e6e6e6}
body.dark .success-ref{background:#1f1a12;border-color:var(--brand)}
body.dark .success-items li,
body.dark .success-totals div,
body.dark .success-totals .grand{border-color:#262b32}
body.dark .help-box{background:#15171b}

/* Mobile */
@media(max-width:600px){
  .success-cta .btn{width:100%;justify-content:center}
  .summary-box{padding:18px}
  .next-steps{padding:20px}
}
</style>

<?php require __DIR__.'/footer.php'; ?>