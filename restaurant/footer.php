</main>

<footer class="site-footer" role="contentinfo">
  <div class="container footer-grid">

    <div class="footer-brand">
      <!-- ============ FOOTER LOGO + TAGLINE ============ -->
      <a href="<?= e(clean_url('')) ?>" class="footer-logo" aria-label="Exclusive Restaurant">
        <span class="logo-brand">
          <img src="<?= e(logo_url()) ?>"
               alt="Exclusive Restaurant"
               class="footer-logo-img"
               onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
          <span class="logo-fallback">
            <span class="logo-mark" aria-hidden="true"><?= icon('utensils', 18) ?></span>
            <span>Exclusive Restaurant</span>
          </span>
          <span class="logo-tagline logo-tagline--footer">
            <span class="logo-tagline-text">Exclusive Restaurant</span>
          </span>
        </span>
      </a>

      <p class="footer-tagline"><?= e(setting($pdo, 'tagline', 'Authentic Kenyan & International Cuisine')) ?></p>

      <div class="socials">
        <a href="#" aria-label="Facebook"><?= icon('facebook', 18) ?></a>
        <a href="#" aria-label="Instagram"><?= icon('instagram', 18) ?></a>
        <a href="#" aria-label="Twitter"><?= icon('twitter', 18) ?></a>
        <a href="https://wa.me/<?= e($wa ?? '254700000000') ?>"
           target="_blank" rel="noopener" aria-label="WhatsApp"><?= icon('whatsapp', 18) ?></a>
      </div>
    </div>

    <div class="footer-col">
      <h5>Contact</h5>
      <p><?= icon('pin', 16) ?> <?= e(setting($pdo, 'address', 'Westlands, Nairobi')) ?></p>
      <p><?= icon('phone', 16) ?>
        <a href="tel:<?= e(setting($pdo, 'phone', '+254700000000')) ?>">
          <?= e(setting($pdo, 'phone', '+254 700 000 000')) ?>
        </a>
      </p>
      <p><?= icon('mail', 16) ?>
        <a href="mailto:<?= e(setting($pdo, 'email', 'hello@restaurant.co.ke')) ?>">
          <?= e(setting($pdo, 'email', 'hello@restaurant.co.ke')) ?>
        </a>
      </p>
    </div>

    <div class="footer-col">
      <h5>Hours</h5>
      <p>Mon–Thu: 7:00 AM – 11:00 PM</p>
      <p>Fri–Sat: 7:00 AM – 12:00 AM</p>
      <p>Sun: 8:00 AM – 10:00 PM</p>
    </div>

    <div class="footer-col">
      <h5>Explore</h5>
      <a href="<?= e(clean_url('menu.php')) ?>">Menu</a>
      <a href="<?= e(clean_url('reserve.php')) ?>">Reserve</a>
      <a href="<?= e(clean_url('events.php')) ?>">Events</a>
      <a href="<?= e(clean_url('contact.php')) ?>">Contact</a>
    </div>
  </div>

  <div class="container footer-bottom">
    <p>© <?= date('Y') ?> Exclusive Restaurant. All rights reserved.</p>
    <p class="footer-credits">Crafted with <?= icon('star', 14) ?> in Nairobi</p>
  </div>
</footer>

<a class="whatsapp-float"
   href="https://wa.me/<?= e($wa ?? '254700000000') ?>?text=Hello%2C%20I%27d%20like%20to%20place%20an%20order."
   target="_blank" rel="noopener"
   aria-label="Order on WhatsApp">
  <?= icon('whatsapp', 24) ?>
  <span>Order on WhatsApp</span>
</a>

<button id="backTop" class="back-top" type="button" aria-label="Back to top">
  <?= icon('arrow', 20) ?>
</button>

<script>window.BASE_URL = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>/script/main.js" defer></script>
<script src="<?= BASE_URL ?>/script/cart.js" defer></script>
<?php require __DIR__.'/chatbot.php'; ?>
</body>
</html>