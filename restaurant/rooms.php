<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';

$page_title = 'Rooms & Suites | ' . setting($pdo, 'site_name', 'Restaurant');

/* Pull active rooms */
$rooms = [];
try {
    $rooms = $pdo->query("SELECT * FROM restaurant_rooms WHERE active=1 ORDER BY price_per_night ASC")->fetchAll();
} catch (PDOException $e) {
    error_log('rooms query failed: '.$e->getMessage());
}

require __DIR__.'/header.php';
?>

<section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=1920&q=80')">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <p class="hero-eyebrow">Rest & relax</p>
    <h1>Our Rooms &amp; Suites</h1>
    <p class="hero-sub">Comfortable stays above our kitchen — wake up to the smell of fresh coffee</p>
  </div>
</section>

<!-- ============ SEARCH BAR ============ -->
<section class="container" style="margin-top:-40px;position:relative;z-index:5">
  <form class="rooms-search" id="roomsSearch" method="get">
    <div class="rs-field">
      <label><?= icon('calendar', 15) ?> Check-in</label>
      <input type="date" name="check_in" id="rsCheckIn" min="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="rs-field">
      <label><?= icon('calendar', 15) ?> Check-out</label>
      <input type="date" name="check_out" id="rsCheckOut" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
    </div>
    <div class="rs-field">
      <label><?= icon('user', 15) ?> Guests</label>
      <select name="guests" id="rsGuests">
        <option value="1">1 guest</option>
        <option value="2" selected>2 guests</option>
        <option value="3">3 guests</option>
        <option value="4">4 guests</option>
        <option value="5">5 guests</option>
      </select>
    </div>
    <button class="btn btn-primary rs-submit" type="submit">
      <?= icon('search', 16) ?> Check availability
    </button>
  </form>
</section>

<!-- ============ ROOM CARDS ============ -->
<section class="container section">
  <header class="section-head">
    <div>
      <p class="section-eyebrow">Choose your stay</p>
      <h2 class="section-title">Rooms &amp; Suites</h2>
    </div>
    <p class="muted" id="nightsLabel" style="color:var(--muted);font-size:.9rem"></p>
  </header>

  <?php if (!$rooms): ?>
    <div class="empty-state">No rooms available at the moment.</div>
  <?php else: ?>
    <div class="rooms-grid">
      <?php foreach ($rooms as $r):
        $amen = array_filter(array_map('trim', explode(',', $r['amenities'] ?? '')));
        $img  = $r['image'] ?: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=1200&q=80';
      ?>
        <article class="room-card" data-id="<?= (int)$r['id'] ?>"
                 data-price="<?= (float)$r['price_per_night'] ?>"
                 data-capacity="<?= (int)$r['capacity'] ?>"
                 data-name="<?= e($r['name']) ?>"
                 data-units="<?= (int)$r['total_units'] ?>">
          <div class="room-photo">
            <img src="<?= e($img) ?>" alt="<?= e($r['name']) ?>" loading="lazy">
            <span class="room-badge"><?= e($r['room_type']) ?></span>
          </div>
          <div class="room-body">
            <h3 class="room-title"><?= e($r['name']) ?></h3>
            <p class="room-desc"><?= e($r['description']) ?></p>

            <ul class="room-facts">
              <li><?= icon('user', 14) ?> Sleeps <?= (int)$r['capacity'] ?></li>
              <?php if ($r['beds']): ?><li><?= icon('bed', 14) ?> <?= e($r['beds']) ?></li><?php endif; ?>
              <?php if ($r['size_sqm']): ?><li><?= icon('expand', 14) ?> <?= (int)$r['size_sqm'] ?> m²</li><?php endif; ?>
            </ul>

            <?php if ($amen): ?>
              <div class="room-amenities">
                <?php foreach ($amen as $a): ?>
                  <span class="amen-pill"><?= e(ucfirst($a)) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="room-footer">
              <div class="room-price">
                <strong>KSh <?= number_format((float)$r['price_per_night']) ?></strong>
                <small>per night</small>
              </div>
              <button type="button" class="btn btn-primary btn-book"
                      data-room='<?= htmlspecialchars(json_encode([
                          "id"    => (int)$r['id'],
                          "name"  => $r['name'],
                          "price" => (float)$r['price_per_night'],
                          "cap"   => (int)$r['capacity'],
                          "units" => (int)$r['total_units'],
                      ]), ENT_QUOTES) ?>'>
                <?= icon('calendar', 15) ?> Book now
              </button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- ============ BOOKING MODAL ============ -->
<div class="modal-backdrop" id="bookModal" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="bookTitle" style="max-width:560px">
    <button type="button" class="modal-close" data-close aria-label="Close">×</button>

    <h2 id="bookTitle" style="margin-bottom:4px">Complete your booking</h2>
    <p class="modal-sub" id="bookSub">Fill in your details to reserve your stay.</p>

    <form id="bookForm" class="auth-modal-form" novalidate>
      <input type="hidden" name="room_id" id="bkRoomId">

      <div class="modal-room-summary" id="bkSummary"></div>

      <div class="bk-grid">
        <div class="auth-field">
          <label for="bkCheckIn">Check-in</label>
          <div class="auth-input-wrap">
            <?= icon('calendar', 18) ?>
            <input id="bkCheckIn" type="date" name="check_in" required min="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="auth-field">
          <label for="bkCheckOut">Check-out</label>
          <div class="auth-input-wrap">
            <?= icon('calendar', 18) ?>
            <input id="bkCheckOut" type="date" name="check_out" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
          </div>
        </div>
      </div>

      <div class="bk-grid">
        <div class="auth-field">
          <label for="bkGuests">Guests</label>
          <div class="auth-input-wrap">
            <?= icon('user', 18) ?>
            <input id="bkGuests" type="number" name="guests" min="1" value="1" required>
          </div>
        </div>
        <div class="auth-field">
          <label for="bkRooms">Rooms</label>
          <div class="auth-input-wrap">
            <?= icon('bed', 18) ?>
            <input id="bkRooms" type="number" name="rooms_count" min="1" value="1" required>
          </div>
        </div>
      </div>

      <div class="auth-field">
        <label for="bkName">Full name</label>
        <div class="auth-input-wrap">
          <?= icon('user', 18) ?>
          <input id="bkName" type="text" name="guest_name" required placeholder="Jane Wanjiru">
        </div>
      </div>

      <div class="bk-grid">
        <div class="auth-field">
          <label for="bkEmail">Email</label>
          <div class="auth-input-wrap">
            <?= icon('mail', 18) ?>
            <input id="bkEmail" type="email" name="guest_email" required placeholder="you@example.com">
          </div>
        </div>
        <div class="auth-field">
          <label for="bkPhone">Phone</label>
          <div class="auth-input-wrap">
            <?= icon('phone', 18) ?>
            <input id="bkPhone" type="tel" name="guest_phone" required placeholder="+254 700 000 000">
          </div>
        </div>
      </div>

      <div class="auth-field">
        <label for="bkNotes">Special requests (optional)</label>
        <div class="auth-input-wrap" style="align-items:flex-start;padding-top:2px">
          <textarea id="bkNotes" name="notes" rows="2" style="padding:12px 14px;padding-left:44px;border:0;background:transparent;margin:0;resize:vertical;width:100%;font:inherit;outline:none" placeholder="Late check-in, high floor, etc."></textarea>
        </div>
      </div>

      <div class="bk-total" id="bkTotal"></div>

      <div class="modal-actions auth-modal-actions">
        <button type="button" class="btn btn-outline-dark" data-close>Cancel</button>
        <button type="submit" class="btn btn-primary auth-modal-submit" id="bkSubmit">
          Confirm booking <?= icon('arrow', 16) ?>
        </button>
      </div>

      <p class="auth-modal-note" style="margin-top:12px">
        <?= icon('lock', 14) ?> No payment required now — we'll confirm by phone or email.
      </p>
    </form>
  </div>
</div>

<!-- ============ SUCCESS MODAL ============ -->
<div class="modal-backdrop" id="bookSuccess" aria-hidden="true">
  <div class="modal modal-small" role="dialog" aria-modal="true" style="text-align:center">
    <div class="success-badge" style="background:var(--brand-tint);color:var(--brand)">
      <?= icon('check', 32) ?>
    </div>
    <h2 style="margin-bottom:6px">Booking received!</h2>
    <p class="modal-sub">We've received your request. Confirmation coming shortly.</p>

    <div class="booking-ref-box">
      <small>Your booking reference</small>
      <strong id="bkRef">—</strong>
    </div>

    <p class="modal-sub" style="margin-top:16px">
      Save this reference. We'll contact you at the phone number you provided within 30 minutes.
    </p>

    <div class="modal-actions auth-modal-actions">
      <a href="#" id="bkWhatsApp" target="_blank" rel="noopener" class="btn btn-whatsapp btn-block">
        <?= icon('whatsapp', 16) ?> Message us on WhatsApp
      </a>
    </div>
    <button type="button" class="auth-modal-resend" data-close style="margin-top:14px">
      <span>Close</span>
    </button>
  </div>
</div>

<script>
/* ============ ROOMS PAGE LOGIC ============ */
(function(){
  const bookModal   = document.getElementById('bookModal');
  const successModal= document.getElementById('bookSuccess');
  const form        = document.getElementById('bookForm');
  const summary     = document.getElementById('bkSummary');
  const totalBox    = document.getElementById('bkTotal');
  const roomIdInput = document.getElementById('bkRoomId');
  const checkIn     = document.getElementById('bkCheckIn');
  const checkOut    = document.getElementById('bkCheckOut');
  const guestsInput = document.getElementById('bkGuests');
  const roomsInput  = document.getElementById('bkRooms');
  const submitBtn   = document.getElementById('bkSubmit');
  const searchForm  = document.getElementById('roomsSearch');
  const nightsLabel = document.getElementById('nightsLabel');

  let currentRoom = null;

  /* --- Open modal when clicking Book Now --- */
  document.querySelectorAll('.btn-book').forEach(btn => {
    btn.addEventListener('click', () => {
      try { currentRoom = JSON.parse(btn.dataset.room); } catch(e){ return; }
      if (!currentRoom) return;

      roomIdInput.value = currentRoom.id;
      guestsInput.max   = currentRoom.cap * currentRoom.units;
      guestsInput.value = Math.min(guestsInput.value || 1, currentRoom.cap);
      roomsInput.max    = currentRoom.units;

      summary.innerHTML = `
        <div class="bk-room-thumb">
          <strong>${currentRoom.name}</strong>
          <span>KSh ${currentRoom.price.toLocaleString()} / night</span>
        </div>`;

      openModal(bookModal);
      recalc();
    });
  });

  /* --- Recalculate total --- */
  function recalc(){
    if (!currentRoom) return;
    const ci = checkIn.value, co = checkOut.value;
    if (!ci || !co) { totalBox.innerHTML = ''; return; }

    const d1 = new Date(ci), d2 = new Date(co);
    const nights = Math.max(0, Math.round((d2 - d1) / 86400000));
    const roomsCount = Math.max(1, parseInt(roomsInput.value) || 1);
    const total = nights * roomsCount * currentRoom.price;

    totalBox.innerHTML = `
      <div class="bk-total-row"><span>${nights} night${nights!==1?'s':''} × ${roomsCount} room${roomsCount!==1?'s':''}</span><span>KSh ${(nights*roomsCount*currentRoom.price).toLocaleString()}</span></div>
      <div class="bk-total-row bk-grand"><span>Total</span><strong>KSh ${total.toLocaleString()}</strong></div>`;
  }
  [checkIn, checkOut, roomsInput, guestsInput].forEach(el => el?.addEventListener('input', recalc));

  /* --- Validate dates on change --- */
  checkIn.addEventListener('change', () => {
    if (checkIn.value) {
      const next = new Date(checkIn.value);
      next.setDate(next.getDate() + 1);
      checkOut.min = next.toISOString().slice(0,10);
      if (checkOut.value && checkOut.value <= checkIn.value) {
        checkOut.value = next.toISOString().slice(0,10);
      }
    }
    recalc();
  });

  /* --- Submit --- */
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);

    // Basic validation
    if (!fd.get('check_in') || !fd.get('check_out')) return alert('Please select your dates.');
    if (new Date(fd.get('check_out')) <= new Date(fd.get('check_in'))) return alert('Check-out must be after check-in.');

    submitBtn.disabled = true;
    const oldText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="auth-spinner"></span> Booking…';

    try {
      const res = await fetch(BASE_URL + '/room-book.php', {
        method: 'POST',
        body: fd,
        headers: {'X-Requested-With': 'XMLHttpRequest'}
      });
      const data = await res.json();

      if (!data.ok) {
        alert(data.error || 'Booking failed. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = oldText;
        return;
      }

      document.getElementById('bkRef').textContent = data.ref;

      // WhatsApp prefilled message
      const waPhone = document.querySelector('a.whatsapp-float')?.href.match(/(\d+)/)?.[1] || '254700000000';
      const msg = `Hi, I just booked room ${currentRoom.name} (ref: ${data.ref}) from ${fd.get('check_in')} to ${fd.get('check_out')}. Please confirm.`;
      document.getElementById('bkWhatsApp').href = `https://wa.me/${waPhone}?text=${encodeURIComponent(msg)}`;

      closeModal(bookModal);
      setTimeout(() => openModal(successModal), 200);

    } catch (err) {
      alert('Network error. Please try again.');
      submitBtn.disabled = false;
      submitBtn.innerHTML = oldText;
    }
  });

  /* --- Search bar --- */
  searchForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    const ci = document.getElementById('rsCheckIn').value;
    const co = document.getElementById('rsCheckOut').value;
    const g  = document.getElementById('rsGuests').value;
    if (!ci || !co) return;
    const nights = Math.max(1, Math.round((new Date(co) - new Date(ci)) / 86400000));
    nightsLabel.textContent = `${nights} night${nights!==1?'s':''} · ${g} guest${g!=1?'s':''}`;
    document.querySelector('.rooms-grid')?.scrollIntoView({behavior:'smooth', block:'start'});
  });

  /* --- Modal helpers --- */
  function openModal(el){
    el.classList.add('open');
    el.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(el){
    el.classList.remove('open');
    el.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
  }
  document.querySelectorAll('[data-close]').forEach(b =>
    b.addEventListener('click', e => closeModal(e.target.closest('.modal-backdrop')))
  );
  [bookModal, successModal].forEach(m =>
    m.addEventListener('click', e => { if (e.target === m) closeModal(m); })
  );
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeModal(bookModal); closeModal(successModal); }
  });
})();
</script>

<?php require __DIR__.'/footer.php'; ?>