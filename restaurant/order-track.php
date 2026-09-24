<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$ref = $_GET['ref'] ?? '';
$st  = $pdo->prepare("SELECT * FROM restaurant_orders WHERE order_ref=?");
$st->execute([$ref]);
$order = $st->fetch();
if (!$order) die('Order not found');

$items = $pdo->prepare("SELECT * FROM restaurant_order_items WHERE order_id=?");
$items->execute([$order['id']]);
$items = $items->fetchAll();

$steps      = ['received', 'preparing', 'ready', 'out_for_delivery', 'delivered'];
$currentIdx = array_search($order['status'], $steps);
if ($currentIdx === false) $currentIdx = 0;

$isCancelled = $order['status'] === 'cancelled';

/* ------------------------------------------------------------------
   Determine when "out_for_delivery" started.
   Priority:
     1) restaurant_orders.out_for_delivery_at (best — set by admin)
     2) Fallback: if status is currently out_for_delivery/delivered,
        assume it just started now (or derived from updated_at)
     3) NULL if the order hasn't reached that stage
------------------------------------------------------------------ */
$outAt = null;
if (!empty($order['out_for_delivery_at'])) {
    $outAt = strtotime($order['out_for_delivery_at']);
} elseif (in_array($order['status'], ['out_for_delivery', 'delivered'], true)) {
    /* Derive from updated_at or created_at as a graceful fallback */
    $fallbackField = $order['updated_at'] ?? $order['created_at'];
    $outAt = strtotime($fallbackField);
}

/* Minimum window (seconds) */
$minOutSeconds = 30 * 60;   // 30 minutes

/* Live ETA info for the JS */
$deliveryEta = null;
if ($outAt) {
    $deliveryEta = $outAt + $minOutSeconds;  // unix timestamp
}

$page_title = 'Track Order · ' . $order['order_ref'];
require __DIR__.'/header.php';
?>

<section class="container section track-page">
  <header class="track-head">
    <div>
      <p class="section-eyebrow">Order tracking</p>
      <h1>Order <code><?= e($order['order_ref']) ?></code></h1>
      <p class="muted">
        Placed on <?= date('d M Y · H:i', strtotime($order['created_at'])) ?>
        · Total <strong><?= money($order['total']) ?></strong>
      </p>
    </div>
    <a href="<?= BASE_URL ?>/menu.php" class="btn btn-outline-dark btn-sm">
      <?= icon('arrow', 14) ?> Continue shopping
    </a>
  </header>

  <?php if ($isCancelled): ?>
    <div class="alert error" role="alert">
      <?= icon('close', 16) ?>
      <strong>This order was cancelled.</strong>
      <?php if (!empty($order['cancel_reason'])): ?>
        <br><small>Reason: <?= e($order['cancel_reason']) ?></small>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- ============ LIVE ETA CARD ============ -->
  <?php if (!$isCancelled && $deliveryEta): ?>
    <div class="eta-card" id="etaCard">
      <div class="eta-pulse"></div>
      <div class="eta-body">
        <small>Estimated arrival</small>
        <strong id="etaText">Calculating…</strong>
        <span class="eta-sub" id="etaSub"></span>
      </div>
    </div>
  <?php elseif (!$isCancelled && $order['status'] === 'out_for_delivery'): ?>
    <div class="eta-card" id="etaCard">
      <div class="eta-pulse"></div>
      <div class="eta-body">
        <small>Estimated arrival</small>
        <strong id="etaText">~30 min</strong>
        <span class="eta-sub">Your rider is on the way</span>
      </div>
    </div>
  <?php endif; ?>

  <!-- ============ TRACKER ============ -->
  <div class="tracker" id="tracker"
       data-status="<?= e($order['status']) ?>"
       data-start="<?= strtotime($order['created_at']) * 1000 ?>"
       data-out-at="<?= $outAt ? $outAt * 1000 : '' ?>"
       data-min-out="<?= $minOutSeconds * 1000 ?>"
       data-ref="<?= e($order['order_ref']) ?>">

    <div class="tracker-rail">
      <div class="tracker-fill" id="trackerFill"></div>
      <div class="tracker-mover" id="trackerMover" aria-hidden="true">
        <?= icon('truck', 18) ?>
      </div>
    </div>

    <?php foreach ($steps as $i => $s): ?>
      <div class="tracker-step <?= $i <= $currentIdx ? 'done' : '' ?> <?= $i === $currentIdx ? 'active' : '' ?>"
           data-step="<?= $s ?>"
           data-index="<?= $i ?>">
        <div class="tracker-dot">
          <?= icon($i < $currentIdx ? 'check' : 'clock', 16) ?>
        </div>
        <div class="tracker-label">
          <?= e(ucfirst(str_replace('_', ' ', $s))) ?>
        </div>
        <div class="tracker-time" data-time="<?= $s ?>"></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- ============ LIVE STATUS PILL ============ -->
  <div class="tracker-status" id="trackerStatus">
    <span class="pulse-dot"></span>
    <span id="statusText"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span>
  </div>

  <!-- ============ ORDER DETAILS ============ -->
  <div class="grid grid-2 track-details">
    <div class="stat-card">
      <small>Delivery to</small>
      <p><strong><?= e($order['customer_name']) ?></strong></p>
      <p class="muted"><?= e($order['phone']) ?></p>
      <p class="muted"><?= e($order['address'] ?: 'Pickup at branch') ?></p>
    </div>

    <div class="stat-card">
      <small>Items (<?= count($items) ?>)</small>
      <ul class="track-items">
        <?php foreach ($items as $it): ?>
          <li>
            <span><?= e($it['name']) ?> × <?= (int)$it['quantity'] ?></span>
            <span><?= money($it['price'] * $it['quantity']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <!-- ============ CANCEL BUTTON ============ -->
  <?php if (!$isCancelled && !in_array($order['status'], ['delivered', 'out_for_delivery'], true)): ?>
    <div class="cancel-wrap">
      <button type="button" class="btn-cancel" id="openCancel">
        <span class="btn-cancel-icon"><?= icon('close', 16) ?></span>
        <span>Cancel Order</span>
      </button>
      <p class="cancel-note">
        <?= icon('check', 12) ?> Free cancellation before your order is out for delivery
      </p>
    </div>
  <?php elseif ($order['status'] === 'out_for_delivery'): ?>
    <div class="cancel-wrap">
      <p class="cancel-locked">
        <?= icon('truck', 14) ?> Your order is on the way — cancellation is no longer available.
      </p>
    </div>
  <?php endif; ?>
</section>

<!-- ============================================================
     CANCEL MODAL
============================================================ -->
<div class="modal-backdrop" id="cancelModal" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="cancelTitle">
    <button type="button" class="modal-close" id="closeCancel" aria-label="Close">✕</button>

    <h2 id="cancelTitle">Why are you cancelling?</h2>
    <p class="modal-sub">Please tell us the reason for cancelling this order. This is required.</p>

    <form id="cancelForm" method="post" action="<?= BASE_URL ?>/cancel-order.php">
      <input type="hidden" name="order_ref" value="<?= e($order['order_ref']) ?>">
      <?= csrf_field() ?>

      <div class="reason-list">
        <?php
          $reasons = [
            'Changed my mind',
            'Ordered by mistake',
            'Waiting time too long',
            'Found a better option',
            'Wrong items in the cart',
            'Price too high',
            'Delivery address is incorrect',
            'Other (please specify below)',
          ];
          foreach ($reasons as $r):
        ?>
          <label class="reason-item">
            <input type="radio" name="cancel_reason" value="<?= e($r) ?>" required>
            <span><?= e($r) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <label class="reason-other">
        Additional details (optional)
        <textarea name="cancel_notes" rows="2" placeholder="Anything else you'd like us to know…"></textarea>
      </label>

      <div class="modal-actions">
        <button type="button" class="btn btn-outline-dark" id="cancelBack">Back</button>
        <button type="submit" class="btn btn-danger" id="confirmCancel" disabled>
          Confirm cancellation
        </button>
      </div>
    </form>
  </div>
</div>

<!-- SUCCESS MODAL -->
<div class="modal-backdrop" id="successModal" aria-hidden="true">
  <div class="modal modal-small" role="dialog" aria-modal="true">
    <div class="success-badge"><?= icon('check', 32) ?></div>
    <h2>Order cancelled</h2>
    <p class="modal-sub">Your order has been successfully cancelled. You'll receive a confirmation shortly.</p>
    <a href="<?= BASE_URL ?>/menu.php" class="btn btn-primary btn-block btn-lg">Back to menu</a>
  </div>
</div>

<script>
/* =========================================================
   1) TRACKING ANIMATION — with 30-min minimum out-for-delivery
========================================================= */
(function () {
  const tracker = document.getElementById('tracker');
  if (!tracker) return;

  const status    = tracker.dataset.status;
  const startTs   = parseInt(tracker.dataset.start, 10);
  const outAtTs   = tracker.dataset.outAt ? parseInt(tracker.dataset.outAt, 10) : 0;
  const minOutMs  = parseInt(tracker.dataset.minOut, 10) || (30 * 60 * 1000);

  const steps = ['received', 'preparing', 'ready', 'out_for_delivery', 'delivered'];

  /* ---------- Step transition delays (before out-for-delivery) ---------- */
  const preOutDelays = {
    preparing: 2000,   // 2s purple
    ready:     2000,   // 2s pink
    out_for_delivery: 3000, // 3s before turning green
  };

  const stepEls   = tracker.querySelectorAll('.tracker-step');
  const fillEl    = document.getElementById('trackerFill');
  const moverEl   = document.getElementById('trackerMover');
  const statusTxt = document.getElementById('statusText');
  const statusBox = document.getElementById('trackerStatus');

  /* ---------- Status → CSS state class ---------- */
  const stateFor = {
    received:         'is-received',
    preparing:        'is-preparing',
    ready:            'is-ready',
    out_for_delivery: 'is-out',
    delivered:        'is-delivered',
  };

  function paint(index, state) {
    stepEls.forEach((el, i) => {
      el.classList.remove('done', 'active', 'is-received', 'is-preparing', 'is-ready', 'is-out', 'is-delivered');
      if (i < index)   el.classList.add('done');
      if (i === index) el.classList.add('active', state);
    });

    const pct = (index / (steps.length - 1)) * 100;
    if (fillEl)  fillEl.style.width = pct + '%';

    /* Move the truck along the rail. Show it only during out_for_delivery. */
    if (moverEl) {
      moverEl.style.left = 'calc(' + pct + '% - 18px)';
      moverEl.classList.toggle('show', steps[index] === 'out_for_delivery');
    }

    if (statusTxt) statusTxt.textContent =
      steps[index].replace(/_/g, ' ').replace(/^\w/, c => c.toUpperCase());

    if (statusBox) statusBox.dataset.state = state.replace('is-', '');
  }

  /* ---------- Start position ---------- */
  let i = steps.indexOf(status);
  if (i < 0) i = 0;
  paint(i, stateFor[steps[i]] || 'is-received');

  /* ---------- If order already delivered / cancelled → freeze ---------- */
  if (status === 'delivered' || status === 'cancelled') return;

  /* ---------- Animate to next stages ---------- */
  function advance() {
    i++;
    if (i >= steps.length) return;

    const nextStep = steps[i];
    paint(i, stateFor[nextStep]);

    if (nextStep === 'delivered') return; // last step — no further action

    /* If the next step is out_for_delivery → schedule the DELIVERED transition
       based on outAtTs + 30 minutes (or from now if outAt not set) */
    if (nextStep === 'out_for_delivery') {
      const outStart = outAtTs || Date.now();
      const targetDelivered = outStart + minOutMs;
      const waitMs = Math.max(targetDelivered - Date.now(), 0);

      /* Cap the wait so the browser tab doesn't break on very long durations.
         We re-check periodically in real life; here we just setTimeout. */
      setTimeout(() => {
        i++;
        paint(i, stateFor.delivered);
      }, waitMs);
      return;
    }

    const delay = preOutDelays[steps[i]] || 2000;
    setTimeout(advance, delay);
  }

  /* First stage pause */
  setTimeout(advance, 1500);
})();

/* =========================================================
   2) LIVE ETA COUNTDOWN
========================================================= */
(function () {
  const tracker = document.getElementById('tracker');
  const etaEl   = document.getElementById('etaText');
  const etaSub  = document.getElementById('etaSub');
  if (!tracker || !etaEl) return;

  const status   = tracker.dataset.status;
  const outAt    = tracker.dataset.outAt ? parseInt(tracker.dataset.outAt, 10) : null;
  const minOutMs = parseInt(tracker.dataset.minOut, 10) || (30 * 60 * 1000);

  if (status !== 'out_for_delivery') return;

  function tick() {
    const start = outAt || Date.now();
    const eta   = start + minOutMs;
    let   ms    = eta - Date.now();

    if (ms <= 0) {
      etaEl.textContent = 'Arriving now';
      if (etaSub) etaSub.textContent = 'Your order should be with you shortly';
      return;
    }

    const mins = Math.floor(ms / 60000);
    const secs = Math.floor((ms % 60000) / 1000);

    if (mins > 0) {
      etaEl.textContent = '~' + mins + ' min' + (secs ? ' ' + secs + 's' : '');
    } else {
      etaEl.textContent = '~' + secs + ' seconds';
    }
    if (etaSub) etaSub.textContent = 'Your rider is on the way';
  }

  tick();
  const timer = setInterval(() => {
    tick();
    const start = outAt || Date.now();
    if (Date.now() - start >= minOutMs) clearInterval(timer);
  }, 1000);
})();

/* =========================================================
   3) CANCEL MODAL
========================================================= */
(function () {
  const openBtn    = document.getElementById('openCancel');
  const modal      = document.getElementById('cancelModal');
  const closeBtn   = document.getElementById('closeCancel');
  const backBtn    = document.getElementById('cancelBack');
  const form       = document.getElementById('cancelForm');
  const confirmBtn = document.getElementById('confirmCancel');
  const radios     = form ? form.querySelectorAll('input[name="cancel_reason"]') : [];

  if (!openBtn || !modal) return;

  const show = el => { el.classList.add('open'); el.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; };
  const hide = el => { el.classList.remove('open'); el.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; };

  openBtn.addEventListener('click', () => show(modal));
  [closeBtn, backBtn].forEach(b => b && b.addEventListener('click', () => hide(modal)));
  modal.addEventListener('click', e => { if (e.target === modal) hide(modal); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('open')) hide(modal); });

  radios.forEach(r => r.addEventListener('change', () => {
    confirmBtn.disabled = !Array.from(radios).some(x => x.checked);
  }));

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!Array.from(radios).some(x => x.checked)) return;

    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Cancelling…';

    try {
      const fd = new FormData(form);
      const r = await fetch(form.action, { method: 'POST', body: fd }).then(res => res.json());

      if (r.ok) {
        hide(modal);
        const sm = document.getElementById('successModal');
        if (sm) { sm.classList.add('open'); sm.setAttribute('aria-hidden', 'false'); }
        setTimeout(() => {
          window.location.href = window.BASE_URL + '/order-track.php?ref=' + encodeURIComponent(r.ref);
        }, 2500);
      } else {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirm cancellation';
        alert(r.msg || 'Could not cancel order.');
      }
    } catch {
      confirmBtn.disabled = false;
      confirmBtn.textContent = 'Confirm cancellation';
      alert('Network error. Please try again.');
    }
  });
})();
</script>

<?php require __DIR__.'/footer.php'; ?>