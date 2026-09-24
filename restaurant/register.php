<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';

$page_title = 'Register | ' . setting($pdo, 'site_name', 'Restaurant');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } else {
        $chk = $pdo->prepare("SELECT id FROM restaurant_users WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'Email already registered.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $st = $pdo->prepare("INSERT INTO restaurant_users (name,email,phone,password_hash) VALUES (?,?,?,?)");
            $st->execute([$name, $email, $phone, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            redirect(BASE_URL.'/account.php');
        }
    }
}
require __DIR__.'/header.php';
?>

<section class="auth-page">
  <div class="auth-shell">

    <!-- ============ SLIDESHOW SIDE ============ -->
    <aside class="auth-visual" aria-hidden="true">
      <div class="auth-slides">
        <div class="auth-slide is-active" style="background-image:url('https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1200&q=80')"></div>
        <div class="auth-slide" style="background-image:url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&q=80')"></div>
        <div class="auth-slide" style="background-image:url('https://images.unsplash.com/photo-1552566626-52f8b828add9?w=1200&q=80')"></div>
        <div class="auth-slide" style="background-image:url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&q=80')"></div>
      </div>
      <div class="auth-visual-overlay"></div>

      <div class="auth-visual-content">
        <span class="auth-visual-eyebrow">Join the table</span>
        <h2 class="auth-visual-title">A warm welcome awaits</h2>
        <p class="auth-visual-sub">Create your account to unlock faster checkout, saved favourites, and exclusive offers.</p>

        <div class="auth-visual-dots" role="tablist" aria-label="Slideshow navigation">
          <button class="auth-dot is-active" type="button" aria-label="Slide 1"></button>
          <button class="auth-dot" type="button" aria-label="Slide 2"></button>
          <button class="auth-dot" type="button" aria-label="Slide 3"></button>
          <button class="auth-dot" type="button" aria-label="Slide 4"></button>
        </div>
      </div>
    </aside>

    <!-- ============ FORM SIDE ============ -->
    <div class="auth-form-wrap">
      <div class="auth-form-inner">
        <span class="auth-eyebrow">Create account</span>
        <h1 class="auth-title">Get started</h1>
        <p class="auth-sub">It only takes a minute. Welcome to Exclusive Restaurant.</p>

        <?php if ($error): ?>
          <div class="alert error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form" novalidate>
          <?= csrf_field() ?>

          <div class="auth-field">
            <label for="name">Full name</label>
            <div class="auth-input-wrap">
              <?= icon('user', 18) ?>
              <input id="name" type="text" name="name" required
                     placeholder="Jane Wanjiru"
                     value="<?= e($_POST['name'] ?? '') ?>">
            </div>
          </div>

          <div class="auth-field">
            <label for="email">Email address</label>
            <div class="auth-input-wrap">
              <?= icon('mail', 18) ?>
              <input id="email" type="email" name="email" required
                     placeholder="you@example.com"
                     value="<?= e($_POST['email'] ?? '') ?>">
            </div>
          </div>

          <div class="auth-field">
            <label for="phone">Phone number <span class="auth-optional">(optional)</span></label>
            <div class="auth-input-wrap">
              <?= icon('phone', 18) ?>
              <input id="phone" type="tel" name="phone"
                     placeholder="+254 700 000 000"
                     value="<?= e($_POST['phone'] ?? '') ?>">
            </div>
          </div>

          <div class="auth-field">
            <label for="password">Password</label>
            <div class="auth-input-wrap">
              <?= icon('lock', 18) ?>
              <input id="password" type="password" name="password" required
                     placeholder="At least 6 characters" minlength="6">
              <button type="button" class="auth-eye" aria-label="Show password"
                      onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';this.classList.toggle('is-open')">
                <?= icon('eye', 18) ?>
              </button>
            </div>
            <small class="auth-hint">Use 6 or more characters with a mix of letters and numbers.</small>
          </div>

          <label class="auth-check auth-terms">
            <input type="checkbox" name="terms" required>
            <span>I agree to the <a href="<?= BASE_URL ?>/terms.php">Terms</a> &amp; <a href="<?= BASE_URL ?>/privacy.php">Privacy Policy</a>.</span>
          </label>

          <button class="btn btn-primary btn-block btn-lg auth-submit">
            Create account <?= icon('arrow', 16) ?>
          </button>
        </form>

        <p class="auth-alt">
          Already have an account?
          <a href="<?= BASE_URL ?>/login.php">Sign in</a>
        </p>

        <div class="auth-trust">
          <?= icon('lock', 14) ?> <span>Your data is encrypted and safe with us</span>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
(function(){
  const slides = document.querySelectorAll('.auth-slide');
  const dots   = document.querySelectorAll('.auth-dot');
  if (!slides.length) return;
  let i = 0;
  function go(n){
    slides[i].classList.remove('is-active');
    dots[i]?.classList.remove('is-active');
    i = (n + slides.length) % slides.length;
    slides[i].classList.add('is-active');
    dots[i]?.classList.add('is-active');
  }
  dots.forEach((d, n) => d.addEventListener('click', () => go(n)));
  setInterval(() => go(i + 1), 3000);
})();
</script>

<?php require __DIR__.'/footer.php'; ?>