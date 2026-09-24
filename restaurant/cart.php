<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';
require_once __DIR__.'/auth.php';

$page_title = 'Checkout | ' . setting($pdo, 'site_name', 'Restaurant');
$cart = $_SESSION['cart'] ?? [];
if (!$cart) redirect(BASE_URL.'/menu.php');

$user = current_user();

/* Compute totals */
$subtotal = 0;
foreach ($cart as $x) {
    $subtotal += $x['price'] * $x['qty'];
}
$deliveryFee = (int)setting($pdo, 'delivery_fee', 200);
$total       = $subtotal + $deliveryFee;
$branches    = $pdo->query("SELECT id,name FROM restaurant_branches WHERE active=1")->fetchAll();

require __DIR__.'/header.php';
?>

<section class="container section">
  <header class="cart-header">
    <div>
      <p class="section-eyebrow">Almost there</p>
      <h1>Your Cart</h1>
      <p class="muted">
        <span id="cart-line-count"><?= count($cart) ?></span>
        <?= count($cart) === 1 ? 'item' : 'items' ?> in your order
      </p>
    </div>
    <a href="<?= BASE_URL ?>/menu.php" class="btn btn-outline-dark btn-sm">
      <?= icon('arrow', 14) ?> Continue shopping
    </a>
  </header>

  <form method="post" action="<?= BASE_URL ?>/place-order.php" class="checkout-form">
    <?= csrf_field() ?>

    <div class="cart-layout">

      <!-- ============ LEFT: CART ITEMS ============ -->
      <div class="cart-items">
        <h2 class="cart-section-title">Order Items</h2>

        <?php foreach ($cart as $item): ?>
          <?php
            $lineTotal = $item['price'] * $item['qty'];
            $img       = $item['image'] ?? '';
          ?>
          <article class="cart-item" data-id="<?= (int)$item['id'] ?>">
            <div class="cart-item-img">
              <img src="<?= e(menu_image_url($img)) ?>"
                   alt="<?= e($item['name']) ?>"
                   loading="lazy"
                   onerror="this.onerror=null;this.src='<?= UPLOAD_URL ?>menu/placeholder.jpg';">
            </div>

            <div class="cart-item-info">
              <h3><?= e($item['name']) ?></h3>
              <p class="cart-item-price"><?= money($item['price']) ?> each</p>
            </div>

            <div class="cart-item-qty">
              <button type="button"
                      class="qty-btn cart-update"
                      data-id="<?= (int)$item['id'] ?>"
                      data-qty="<?= (int)$item['qty'] - 1 ?>"
                      aria-label="Decrease quantity">−</button>
              <span class="qty-value"><?= (int)$item['qty'] ?></span>
              <button type="button"
                      class="qty-btn cart-update"
                      data-id="<?= (int)$item['id'] ?>"
                      data-qty="<?= (int)$item['qty'] + 1 ?>"
                      aria-label="Increase quantity">+</button>
            </div>

            <div class="cart-item-total">
              <span class="cart-item-line-total"><?= money($lineTotal) ?></span>
            </div>

            <button type="button"
                    class="cart-item-remove cart-remove"
                    data-id="<?= (int)$item['id'] ?>"
                    aria-label="Remove <?= e($item['name']) ?>">
              <?= icon('close', 16) ?>
            </button>
          </article>
        <?php endforeach; ?>

        <div class="cart-notes">
          <label>Special instructions for your order
            <textarea name="notes" rows="2" placeholder="e.g. no onions, extra chili, allergy notes…"></textarea>
          </label>
        </div>
      </div>

      <!-- ============ RIGHT: SUMMARY + DETAILS ============ -->
      <aside class="cart-side">

        <div class="cart-summary">
          <h2 class="cart-section-title">Order Summary</h2>

          <div class="summary-row">
            <span>Subtotal</span>
            <span id="summary-subtotal"><?= money($subtotal) ?></span>
          </div>
          <div class="summary-row">
            <span>Delivery fee</span>
            <span id="summary-delivery"><?= money($deliveryFee) ?></span>
          </div>
          <div class="summary-row summary-total">
            <span>Total</span>
            <span id="summary-total"><?= money($total) ?></span>
          </div>

          <div class="cart-trust">
            <?= icon('check', 14) ?> Free cancellation before preparation
          </div>
        </div>

        <div class="cart-details">
          <h2 class="cart-section-title">Your Details</h2>

          <label>Full Name *
            <input type="text" name="name" required value="<?= e($user['name'] ?? '') ?>">
          </label>
          <label>Phone *
            <input type="tel" name="phone" required value="<?= e($user['phone'] ?? '') ?>">
          </label>
          <label>Email
            <input type="email" name="email" value="<?= e($user['email'] ?? '') ?>">
          </label>

          <h3 class="cart-sub-title">Order Type</h3>
          <div class="radio-row">
            <label><input type="radio" name="order_type" value="delivery" checked> Delivery</label>
            <label><input type="radio" name="order_type" value="pickup"> Pickup</label>
          </div>

          <label>Branch
            <select name="branch_id">
              <?php foreach ($branches as $b): ?>
                <option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label>Delivery Address
            <textarea name="address" rows="3"></textarea>
          </label>

          <h3 class="cart-sub-title">Payment Method</h3>
          <div class="radio-row vertical">
            <label><input type="radio" name="payment_method" value="mpesa" checked> M-Pesa</label>
            <label><input type="radio" name="payment_method" value="cash"> Cash on Delivery</label>
            <label><input type="radio" name="payment_method" value="card" disabled> Card (Coming soon)</label>
          </div>

          <button type="submit" class="btn btn-primary btn-lg btn-block cart-place-order">
            <?= icon('check', 16) ?> Place Order · <?= money($total) ?>
          </button>

          <p class="cart-secure">
            <?= icon('check', 12) ?> Secure checkout · Your data is safe
          </p>
        </div>
      </aside>
    </div>
  </form>
</section>

<?php require __DIR__.'/footer.php'; ?>