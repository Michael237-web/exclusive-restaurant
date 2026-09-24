<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';

$page_title = 'Login | ' . setting($pdo, 'site_name', 'Restaurant');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $st = $pdo->prepare("SELECT * FROM restaurant_users WHERE email=?");
    $st->execute([$email]);
    $u = $st->fetch();

    if ($u && password_verify($pass, $u['password_hash'])) {
        $_SESSION['user_id'] = $u['id'];
        if ($u['role'] === 'admin') redirect(BASE_URL.'/admin/dashboard.php');
        redirect(BASE_URL.'/account.php');
    }
    $error = 'Invalid credentials';
}
require __DIR__.'/header.php';
?>

<section class="auth-page">
  <div class="auth-shell">

    <!-- ============ SLIDESHOW SIDE ============ -->
    <aside class="auth-visual" aria-hidden="true">
      <div class="auth-slides">
        <div class="auth-slide is-active" style="background-image:url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&q=80')"></div>
        <div class="auth-slide" style="background-image:url('https://images.unsplash.com/photo-1552566626-52f8b828add9?w=1200&q=80')"></div>
        <div class="auth-slide" style="background-image:url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&q=80')"></div>
        <div class="auth-slide" style="background-image:url('https://images.unsplash.com/photo-1600891964092-4316c288032e?w=1200&q=80')"></div>
      </div>
      <div class="auth-visual-overlay"></div>

      <div class="auth-visual-content">
        <span class="auth-visual-eyebrow">Exclusive Restaurant</span>
        <h2 class="auth-visual-title">Welcome back to the table</h2>
        <p class="auth-visual-sub">Order your favourites, track deliveries, and reserve your spot in seconds.</p>

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
        <span class="auth-eyebrow">Sign in</span>
        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-sub">Enter your details to access your account.</p>

        <?php if ($error): ?>
          <div class="alert error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form" novalidate>
          <?= csrf_field() ?>

          <div class="auth-field">
            <label for="email">Email address</label>
            <div class="auth-input-wrap">
              <?= icon('mail', 18) ?>
              <input id="email" type="email" name="email" required autofocus
                     placeholder="you@example.com"
                     value="<?= e($_POST['email'] ?? '') ?>">
            </div>
          </div>

          <div class="auth-field">
            <label for="password">Password</label>
            <div class="auth-input-wrap">
              <?= icon('lock', 18) ?>
              <input id="password" type="password" name="password" required
                     placeholder="••••••••" minlength="6">
              <button type="button" class="auth-eye" aria-label="Show password"
                      onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';this.classList.toggle('is-open')">
                <?= icon('eye', 18) ?>
              </button>
            </div>
          </div>

          <div class="auth-row">
            <label class="auth-check">
              <input type="checkbox" name="remember" value="1">
              <span>Remember me</span>
            </label>

            <button type="button" class="auth-link" id="openForgot">
              <span class="auth-link-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                  <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
              </span>
              <span>Forgot password?</span>
            </button>
          </div>

          <button class="btn btn-primary btn-block btn-lg auth-submit">
            Sign in <?= icon('arrow', 16) ?>
          </button>
        </form>

        <p class="auth-alt">
          Don't have an account?
          <a href="<?= BASE_URL ?>/register.php">Create one</a>
        </p>

        <div class="auth-trust">
          <?= icon('lock', 14) ?> <span>Secure login · 256-bit SSL encrypted</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ============ FORGOT PASSWORD — STEP 1 (email) ============ -->
  <div class="modal-backdrop" id="forgotModal" aria-hidden="true">
    <div class="modal auth-modal" role="dialog" aria-modal="true" aria-labelledby="forgotTitle">
      <button type="button" class="modal-close" data-close aria-label="Close">×</button>

      <div class="auth-modal-icon">
        <?= icon('lock', 26) ?>
      </div>

      <h2 id="forgotTitle">Reset your password</h2>
      <p class="modal-sub">Enter the email address linked to your account and we'll send you a secure reset link.</p>

      <form id="forgotForm" class="auth-modal-form" novalidate>
        <div class="auth-field">
          <label for="forgotEmail">Email address</label>
          <div class="auth-input-wrap">
            <?= icon('mail', 18) ?>
            <input id="forgotEmail" type="email" name="email" required
                   placeholder="you@example.com" autocomplete="email">
          </div>
        </div>

        <div class="modal-actions auth-modal-actions">
          <button type="button" class="btn btn-outline-dark" data-close>Cancel</button>
          <button type="submit" class="btn btn-primary auth-modal-submit">
            Send reset link <?= icon('arrow', 16) ?>
          </button>
        </div>

        <p class="auth-modal-note">
          <?= icon('lock', 14) ?> We'll never share your email with anyone.
        </p>
      </form>
    </div>
  </div>

  <!-- ============ FORGOT PASSWORD — STEP 2 (success) ============ -->
  <div class="modal-backdrop" id="sentModal" aria-hidden="true">
    <div class="modal modal-small auth-modal" role="dialog" aria-modal="true" aria-labelledby="sentTitle">
      <button type="button" class="modal-close" data-close aria-label="Close">×</button>

      <div class="success-badge">
        <?= icon('mail', 32) ?>
      </div>

      <h2 id="sentTitle">Check your inbox</h2>
      <p class="modal-sub">
        We've sent a password reset link to<br>
        <strong id="sentEmail" class="auth-sent-email">your email</strong>
      </p>

      <div class="auth-modal-tips">
        <p><?= icon('clock', 14) ?> The link expires in 30 minutes.</p>
        <p><?= icon('info', 14) ?> Didn't get it? Check your spam folder.</p>
      </div>

      <div class="modal-actions auth-modal-actions">
        <button type="button" class="btn btn-primary btn-block" data-close>
          Got it
        </button>
      </div>

      <button type="button" class="auth-modal-resend" id="resendLink">
        <span class="auth-resend-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 4 23 10 17 10"/>
            <polyline points="1 20 1 14 7 14"/>
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"/>
            <path d="M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/>
          </svg>
        </span>
        <span class="auth-resend-text">Resend email</span>
      </button>
    </div>
  </div>
</section>

<script>
/* ============================================================
   AUTH SLIDESHOW
   ============================================================ */
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
  setInterval(() => go(i + 1), 5500);
})();

/* ============================================================
   FORGOT PASSWORD — 2-STEP MODAL FLOW
   ============================================================ */
(function(){
  const forgotModal = document.getElementById('forgotModal');
  const sentModal   = document.getElementById('sentModal');
  const openBtn     = document.getElementById('openForgot');
  const form        = document.getElementById('forgotForm');
  const emailInput  = document.getElementById('forgotEmail');
  const sentEmail   = document.getElementById('sentEmail');
  const resendBtn   = document.getElementById('resendLink');
  const resendText  = resendBtn?.querySelector('.auth-resend-text');

  if (!forgotModal || !sentModal) return;

  function openModal(el){
    el.classList.add('open');
    el.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    const focusable = el.querySelector('input, button:not([data-close]), [tabindex]');
    focusable?.focus();
  }
  function closeModal(el){
    el.classList.remove('open');
    el.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }
  function closeAll(){
    closeModal(forgotModal);
    closeModal(sentModal);
  }

  // Open the first modal
  openBtn?.addEventListener('click', () => openModal(forgotModal));

  // Close buttons
  document.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', (e) => closeModal(e.target.closest('.modal-backdrop')));
  });

  // Click backdrop to close
  [forgotModal, sentModal].forEach(m => {
    m.addEventListener('click', (e) => { if (e.target === m) closeModal(m); });
  });

  // Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAll();
  });

  // Submit step 1 → show step 2
  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    const email = emailInput.value.trim();
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

    if (!valid) {
      emailInput.closest('.auth-input-wrap').classList.add('is-invalid');
      emailInput.focus();
      return;
    }
    emailInput.closest('.auth-input-wrap').classList.remove('is-invalid');

    sentEmail.textContent = email;

    // Fake loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="auth-spinner"></span> Sending…';

    setTimeout(() => {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
      closeModal(forgotModal);
      setTimeout(() => openModal(sentModal), 200);
    }, 900);
  });

  // Resend
  resendBtn?.addEventListener('click', () => {
    if (!resendText || resendBtn.disabled) return;
    resendBtn.disabled = true;
    resendText.textContent = 'Sending…';

    setTimeout(() => {
      resendBtn.disabled = false;
      resendText.textContent = 'Email sent ✓';

      setTimeout(() => {
        resendText.textContent = 'Resend email';
      }, 2400);
    }, 900);
  });

  // Clear invalid state while typing
  emailInput?.addEventListener('input', () => {
    emailInput.closest('.auth-input-wrap').classList.remove('is-invalid');
  });
})();
</script>

<?php require __DIR__.'/footer.php'; ?>