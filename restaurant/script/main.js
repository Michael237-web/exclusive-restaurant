/* =========================================================
   main.js — nav, theme, toast, lightbox, auto-scroll, sticky
   ========================================================= */

(function () {
  'use strict';

  /* --------- Mobile nav (simple display toggle) --------- */
  const toggle = document.querySelector('.nav-toggle');
  const nav    = document.querySelector('.nav');

  if (toggle && nav) {
    const isMobile = () => window.matchMedia('(max-width: 900px)').matches;

    const close = () => {
      nav.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Open navigation');
      document.body.style.overflow = '';
    };

    const open = () => {
      nav.classList.add('open');
      toggle.setAttribute('aria-expanded', 'true');
      toggle.setAttribute('aria-label', 'Close navigation');
      nav.scrollTop = 0;
    };

    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      nav.classList.contains('open') ? close() : open();
    });

    document.addEventListener('click', (e) => {
      if (!nav.classList.contains('open')) return;
      if (nav.contains(e.target) || toggle.contains(e.target)) return;
      close();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && nav.classList.contains('open')) {
        close();
        toggle.focus();
      }
    });

    nav.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        if (isMobile()) close();
      });
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 900) close();
    }, { passive: true });
  }


  /* --------- Theme toggle --------- */
  const themeBtn = document.getElementById('themeToggle');
  if (themeBtn) {
    const stored      = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (stored === 'dark' || (!stored && prefersDark)) document.body.classList.add('dark');

    themeBtn.addEventListener('click', () => {
      const dark = document.body.classList.toggle('dark');
      localStorage.setItem('theme', dark ? 'dark' : 'light');
    });
  }


  /* --------- Back to top --------- */
  const backTop = document.getElementById('backTop');
  if (backTop) {
    window.addEventListener('scroll', () => {
      backTop.classList.toggle('show', window.scrollY > 400);
    }, { passive: true });

    backTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }


  /* --------- Coupon copy --------- */
  document.querySelectorAll('.coupon[data-copy]').forEach(btn => {
    btn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(btn.dataset.copy);
        const hint = btn.querySelector('.copy-hint');
        const old  = hint.textContent;
        hint.textContent = 'Copied!';
        setTimeout(() => hint.textContent = old, 1500);
      } catch { /* ignore */ }
    });
  });


  /* --------- Global toast helper --------- */
  window.toast = function (msg) {
    let t = document.querySelector('.toast');
    if (!t) {
      t = document.createElement('div');
      t.className = 'toast';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    requestAnimationFrame(() => t.classList.add('show'));
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 2400);
  };


  /* --------- Lightbox --------- */
  const lightboxItems = document.querySelectorAll('[data-lightbox]');
  if (lightboxItems.length) {
    let lb = null;
    const open = (src) => {
      if (!lb) {
        lb = document.createElement('div');
        lb.className = 'lightbox';
        lb.innerHTML = '<img alt="">';
        lb.addEventListener('click', close);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
        document.body.appendChild(lb);
      }
      lb.querySelector('img').src = src;
      lb.classList.add('open');
    };
    const close = () => lb && lb.classList.remove('open');

    lightboxItems.forEach(a => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        open(a.getAttribute('href'));
      });
    });
  }
})();


/* =========================================================
   STICKY HEADER — add shadow when scrolled
   ========================================================= */
(function () {
  'use strict';
  const header = document.getElementById('siteHeader');
  if (!header) return;

  const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
})();


/* =========================================================
   CART COUNT — bounce animation when it changes
   ========================================================= */
(function () {
  'use strict';
  const badge = document.getElementById('cart-count');
  if (!badge) return;

  const observer = new MutationObserver(() => {
    badge.classList.remove('is-bumped');
    void badge.offsetWidth;
    badge.classList.add('is-bumped');
  });

  observer.observe(badge, { childList: true, characterData: true, subtree: true });
})();


/* =========================================================
   Clean URLs — strip ".php" from all internal links
   ========================================================= */
(function () {
  'use strict';

  const BASE = window.BASE_URL || '';

  function cleanHref(href) {
    if (!href) return href;
    const isSameOrigin =
      href.startsWith('/') ||
      href.startsWith(BASE + '/') ||
      href.startsWith(location.origin + BASE);

    if (!isSameOrigin) return href;

    let cleaned = href.replace(/\.php(?=$|[?#])/i, '');
    cleaned = cleaned.replace(/\/index(?=$|[?#])/i, '/');

    return cleaned;
  }

  function processAnchor(a) {
    const raw = a.getAttribute('href');
    if (!raw) return;
    const cleaned = cleanHref(raw);
    if (cleaned !== raw) a.setAttribute('href', cleaned);
  }

  function processForm(form) {
    const raw = form.getAttribute('action');
    if (!raw) return;
    const cleaned = cleanHref(raw);
    if (cleaned !== raw) form.setAttribute('action', cleaned);
  }

  function processAll(root) {
    (root || document).querySelectorAll('a[href]').forEach(processAnchor);
    (root || document).querySelectorAll('form[action]').forEach(processForm);
  }

  processAll();
  document.addEventListener('DOMContentLoaded', () => processAll());

  const mo = new MutationObserver(mutations => {
    for (const m of mutations) {
      for (const node of m.addedNodes) {
        if (node.nodeType !== 1) continue;
        if (node.tagName === 'A') processAnchor(node);
        if (node.tagName === 'FORM') processForm(node);
        processAll(node);
      }
    }
  });
  mo.observe(document.documentElement, { childList: true, subtree: true });
})();


/* =========================================================
   AUTO-SCROLL TO RESULTS — smooth easing + entrance animation
   ========================================================= */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {

    const path = location.pathname;
    if (!/(^|\/)menu(\.php)?$/i.test(path)) return;

    const params = new URLSearchParams(location.search);
    const hasCategory = params.has('cat');
    const hasFilter   = params.has('q') || params.has('veg') ||
                        params.has('spicy') || params.has('pop') ||
                        params.has('sort') || params.has('all');

    const hasHash = location.hash === '#results';

    if (!hasCategory && !hasFilter && !hasHash) return;

    const target = document.getElementById('results');
    if (!target) return;

    target.classList.add('is-filtered');

    function easeInOutCubic(t) {
      return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }

    function smoothScrollTo(targetY, duration = 900) {
      const startY   = window.pageYOffset;
      const distance = targetY - startY;
      const startTime = performance.now();

      function step(now) {
        const elapsed  = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased    = easeInOutCubic(progress);
        window.scrollTo(0, startY + distance * eased);
        if (progress < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    function revealSection() {
      target.classList.add('is-visible');

      const cards = target.querySelectorAll('.menu-card');
      cards.forEach((card, i) => {
        card.style.animationDelay = (i * 60) + 'ms';
        card.classList.add('fade-up');
      });

      target.classList.add('is-highlighted');
      setTimeout(() => target.classList.remove('is-highlighted'), 1400);

      const activeCat = document.querySelector('.cat-card.is-active');
      if (activeCat) {
        activeCat.classList.add('is-clicked');
        setTimeout(() => activeCat.classList.remove('is-clicked'), 700);
      }
    }

    setTimeout(() => {
      const headerEl = document.querySelector('.site-header');
      const headerH  = headerEl ? headerEl.offsetHeight : 0;
      const top      = target.getBoundingClientRect().top + window.scrollY - headerH - 16;

      smoothScrollTo(top, 900);
      setTimeout(revealSection, 150);
    }, 100);
  });
})();


/* =========================================================
   SAFETY NET — if JS errored, un-hide results after 3s
   ========================================================= */
setTimeout(() => {
  const r = document.getElementById('results');
  if (r && r.classList.contains('is-filtered') && !r.classList.contains('is-visible')) {
    r.classList.add('is-visible');
    r.querySelectorAll('.menu-card').forEach(c => c.classList.add('fade-up'));
  }
}, 3000);