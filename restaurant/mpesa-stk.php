<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$ref = $_GET['ref'] ?? '';
$st = $pdo->prepare("SELECT * FROM restaurant_orders WHERE order_ref=?");
$st->execute([$ref]);
$order = $st->fetch();
if (!$order) die('Order not found');

/* Mark as pending payment (no real STK push in demo) */
$pdo->prepare("UPDATE restaurant_orders SET payment_status='pending' WHERE id=?")
    ->execute([$order['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Waiting for M-Pesa · <?= e(setting($pdo, 'site_name', 'Restaurant')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
<style>
  body{background:#0e0e10;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0}
  .pay-wrap{max-width:480px;padding:40px 30px;text-align:center;background:#16181c;border:1px solid #23262c;border-radius:20px;box-shadow:0 30px 80px rgba(0,0,0,.6)}
  .pay-icon{width:80px;height:80px;border-radius:50%;background:rgba(230,126,34,.15);color:#e67e22;display:grid;place-items:center;margin:0 auto 20px;font-size:2rem}
  .pay-wrap h1{font-size:1.6rem;letter-spacing:-.02em;margin-bottom:10px}
  .pay-wrap p{color:#a8a8a8;line-height:1.6;margin-bottom:6px}
  .pay-phone{color:#fff;font-weight:700;letter-spacing:.02em}
  .pay-amount{color:#e67e22;font-weight:800;font-size:1.2rem}
  .countdown-ring{position:relative;width:140px;height:140px;margin:26px auto}
  .countdown-ring svg{transform:rotate(-90deg);width:100%;height:100%}
  .countdown-ring circle{fill:none;stroke-width:8;stroke-linecap:round}
  .ring-bg{stroke:#23262c}
  .ring-fg{stroke:#e67e22;stroke-dasharray:408;stroke-dashoffset:408;transition:stroke-dashoffset 1s linear}
  .countdown-num{position:absolute;inset:0;display:grid;place-items:center;font-size:2.6rem;font-weight:800;color:#fff}
  .pay-expired{display:none;animation:fadeIn .3s}
  .pay-expired.show{display:block}
  .pay-active.hide{display:none}
  @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
  .btn-redo{display:inline-flex;align-items:center;gap:8px;margin-top:22px;padding:14px 28px;background:#e67e22;color:#fff;font-weight:700;border-radius:12px;text-decoration:none;transition:background .2s}
  .btn-redo:hover{background:#c96a12}
</style>
</head>
<body>

<div class="pay-wrap">

  <div class="pay-active" id="payActive">
    <div class="pay-icon">📱</div>
    <h1>Check your phone</h1>
    <p>We sent an M-Pesa request to</p>
    <p class="pay-phone"><?= e($order['phone']) ?></p>
    <p>Enter your M-Pesa PIN to pay</p>
    <p class="pay-amount"><?= money($order['total']) ?></p>

    <div class="countdown-ring">
      <svg viewBox="0 0 140 140">
        <circle class="ring-bg" cx="70" cy="70" r="65"></circle>
        <circle class="ring-fg" cx="70" cy="70" r="65" id="ring"></circle>
      </svg>
      <div class="countdown-num" id="countdown">10</div>
    </div>

    <p style="font-size:.85rem">This is a demo. No real payment will be taken.</p>
  </div>

  <div class="pay-expired" id="payExpired">
    <div class="pay-icon" style="background:rgba(220,53,69,.15);color:#dc3545">⏱</div>
    <h1>Time expired</h1>
    <p>Your M-Pesa request timed out.</p>
    <p>Please try again to complete your order.</p>
    <a href="<?= BASE_URL ?>/cart" class="btn-redo">🔄 Try again</a>
  </div>

</div>

<script>
(function(){
  let seconds = 10;
  const num  = document.getElementById('countdown');
  const ring = document.getElementById('ring');
  const circumference = 2 * Math.PI * 65; // ≈ 408.4
  ring.style.strokeDasharray = circumference;

  const tick = setInterval(() => {
    seconds--;
    num.textContent = seconds;

    /* ring progress: 1 → 0 */
    const offset = circumference * (1 - seconds / 10);
    ring.style.strokeDashoffset = -offset * -1; // grows visually

    if (seconds <= 0) {
      clearInterval(tick);
      document.getElementById('payActive').classList.add('hide');
      setTimeout(() => {
        document.getElementById('payExpired').classList.add('show');
      }, 150);
    }
  }, 1000);
})();
</script>

</body>
</html>