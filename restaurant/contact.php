<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';

$page_title = 'Contact Us | ' . setting($pdo, 'site_name', 'Restaurant');

$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $msg   = trim($_POST['message'] ?? '');

    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$msg) {
        $error = 'Please fill in all required fields with a valid email.';
    } else {
        $st = $pdo->prepare("INSERT INTO restaurant_contact_messages (name,email,phone,message) VALUES (?,?,?,?)");
        $st->execute([$name, $email, $phone, $msg]);
        $sent = true;
    }
}

require __DIR__.'/header.php';
?>

<section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1592861956120-e524fc739696?w=1920&q=80')">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <p class="hero-eyebrow">Say hello</p>
    <h1>Contact Us</h1>
    <p class="hero-sub">We'd love to hear from you — expect a reply within 24 hours</p>
  </div>
</section>

<section class="container section">
  <div class="grid grid-2 split" style="gap:60px">
    <div>
      <?php if ($sent): ?>
        <div class="alert success" role="status">
          <?= icon('check', 16) ?> Thank you! We've received your message and will reply shortly.
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert error" role="alert"><?= e($error) ?></div>
      <?php endif; ?>

      <h2>Send us a message</h2>
      <form method="post" class="contact-form">
        <?= csrf_field() ?>
        <label>Your Name *<input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>"></label>
        <label>Email *<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
        <label>Phone<input type="tel" name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></label>
        <label>Message *<textarea name="message" rows="5" required><?= e($_POST['message'] ?? '') ?></textarea></label>
        <button class="btn btn-primary btn-lg">Send Message</button>
      </form>
    </div>

    <div>
      <h2>Visit Us</h2>
      <div class="contact-info">
        <p><?= icon('pin', 18) ?> <?= e(setting($pdo, 'address', 'Westlands, Nairobi')) ?></p>
        <p><?= icon('phone', 18) ?> <a href="tel:<?= e(setting($pdo, 'phone', '+254700000000')) ?>"><?= e(setting($pdo, 'phone', '+254 700 000 000')) ?></a></p>
        <p><?= icon('mail', 18) ?> <a href="mailto:<?= e(setting($pdo, 'email', 'hello@restaurant.co.ke')) ?>"><?= e(setting($pdo, 'email', 'hello@restaurant.co.ke')) ?></a></p>
        <p><?= icon('whatsapp', 18) ?> <a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting($pdo, 'whatsapp', '254700000000'))) ?>" target="_blank" rel="noopener">Chat on WhatsApp</a></p>
        <p><?= icon('clock', 18) ?> Mon–Sun: 7:00 AM – 11:00 PM</p>
      </div>

      <iframe
        src="https://www.google.com/maps?q=<?= urlencode(setting($pdo, 'address', 'Westlands, Nairobi')) ?>&output=embed"
        width="100%" height="320" style="border:0;border-radius:12px;margin-top:20px" loading="lazy"
        title="Our location"></iframe>
    </div>
  </div>
</section>

<?php require __DIR__.'/footer.php'; ?>