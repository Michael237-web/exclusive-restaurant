/* =========================================================
   cart.js — add / update / remove cart items via AJAX
   Live-updates cart totals without page reloads.
   ========================================================= */

(function () {
  'use strict';

  /* ------------------------------------------------------------------
     Helper: parse a "KSh 1,234" string to a number
  ------------------------------------------------------------------ */
  function parseMoney(text) {
    if (!text) return 0;
    return parseFloat(String(text).replace(/[^\d.]/g, '')) || 0;
  }

  /* ------------------------------------------------------------------
     Helper: format a number as "KSh 1,234"
  ------------------------------------------------------------------ */
  function formatMoney(n) {
    return 'KSh ' + Math.round(n).toLocaleString();
  }

  /* ------------------------------------------------------------------
     Helper: read delivery fee from the page (data attribute on <body>)
     Falls back to 0 if not present.
  ------------------------------------------------------------------ */
  function getDeliveryFee() {
    const el = document.querySelector('[data-delivery-fee]');
    return el ? parseFloat(el.dataset.deliveryFee) || 0 : 0;
  }

  /* ------------------------------------------------------------------
     Update the header cart badge
  ------------------------------------------------------------------ */
  function updateHeaderCount(count) {
    const el = document.getElementById('cart-count');
    if (el && count !== undefined) el.textContent = count;
  }

  /* ------------------------------------------------------------------
     Recalculate and repaint the order summary panel
     (subtotal, delivery, total, Place Order button label)
  ------------------------------------------------------------------ */
  function refreshSummary() {
    const rows = document.querySelectorAll('.cart-item');
    let subtotal = 0;

    rows.forEach(row => {
      const priceEl = row.querySelector('.cart-item-price');
      const qtyEl   = row.querySelector('.qty-value');
      const lineEl  = row.querySelector('.cart-item-line-total');
      if (!priceEl || !qtyEl || !lineEl) return;

      const unitPrice = parseMoney(priceEl.textContent);
      const qty       = parseInt(qtyEl.textContent, 10) || 0;
      const lineTotal = unitPrice * qty;

      lineEl.textContent = formatMoney(lineTotal);
      subtotal += lineTotal;
    });

    const delivery = getDeliveryFee();
    const total    = subtotal + delivery;

    const subEl = document.getElementById('summary-subtotal');
    const delEl = document.getElementById('summary-delivery');
    const totEl = document.getElementById('summary-total');
    if (subEl) subEl.textContent = formatMoney(subtotal);
    if (delEl) delEl.textContent = formatMoney(delivery);
    if (totEl) totEl.textContent = formatMoney(total);

    const placeBtn = document.querySelector('.cart-place-order');
    if (placeBtn) {
      placeBtn.innerHTML = '✓ Place Order · ' + formatMoney(total);
    }

    const lineCount = document.getElementById('cart-line-count');
    if (lineCount) lineCount.textContent = rows.length;
  }

  /* ------------------------------------------------------------------
     If the cart becomes empty (last item removed), show empty state
  ------------------------------------------------------------------ */
  function handleEmptyCart() {
    const rows = document.querySelectorAll('.cart-item');
    if (rows.length > 0) return;

    const itemsBox = document.querySelector('.cart-items');
    if (itemsBox) {
      itemsBox.innerHTML =
        '<p class="empty-state">' +
        'Your cart is empty. <a href="' + window.BASE_URL + '/menu.php">Browse the menu</a>' +
        '</p>';
    }
    const side = document.querySelector('.cart-side');
    if (side) side.style.display = 'none';
  }

  /* ==================================================================
     GLOBAL CLICK HANDLER (delegated)
  ================================================================== */
  document.addEventListener('click', async (e) => {

    /* =============================================================
       1) ADD TO CART
    ============================================================= */
    const addBtn = e.target.closest('.add-to-cart');
    if (addBtn) {
      e.preventDefault();
      if (addBtn.disabled) return;

      const original = addBtn.innerHTML;
      addBtn.disabled = true;
      addBtn.innerHTML = 'Adding…';

      try {
        const fd = new FormData();
        fd.append('id', addBtn.dataset.id);

        const r = await fetch(window.BASE_URL + '/cart-add.php', {
          method: 'POST',
          body: fd
        }).then(res => res.json());

        if (r.ok) {
          updateHeaderCount(r.count);
          addBtn.innerHTML = '✓ Added';
          if (window.toast) window.toast('Added to cart');
          setTimeout(() => {
            addBtn.innerHTML = original;
            addBtn.disabled = false;
          }, 1100);
        } else {
          addBtn.innerHTML = original;
          addBtn.disabled = false;
          if (window.toast) window.toast(r.msg || 'Could not add item');
        }
      } catch (err) {
        addBtn.innerHTML = original;
        addBtn.disabled = false;
        if (window.toast) window.toast('Network error');
      }
      return;
    }

    /* =============================================================
       2) UPDATE QUANTITY (live, no reload)
    ============================================================= */
    const updateBtn = e.target.closest('.cart-update');
    if (updateBtn) {
      e.preventDefault();
      if (updateBtn.disabled) return;

      const row    = updateBtn.closest('.cart-item');
      const newQty = parseInt(updateBtn.dataset.qty, 10);

      /* If qty drops to 0, treat as remove */
      if (newQty <= 0) {
        const fd = new FormData();
        fd.append('id', updateBtn.dataset.id);
        await fetch(window.BASE_URL + '/cart-remove.php', { method: 'POST', body: fd });

        if (row) {
          row.style.transition = 'opacity .25s, transform .25s';
          row.style.opacity    = '0';
          row.style.transform  = 'translateX(-24px)';
          setTimeout(() => {
            row.remove();
            refreshSummary();
            handleEmptyCart();
            /* Refresh header count by asking the server */
            fetch(window.BASE_URL + '/cart-remove.php', {
              method: 'POST',
              body: (() => { const f = new FormData(); f.append('id', 0); return f; })()
            }).catch(() => {});
          }, 250);
        }
        return;
      }

      /* Fetch updated cart from server */
      try {
        const fd = new FormData();
        fd.append('id',  updateBtn.dataset.id);
        fd.append('qty', newQty);

        const r = await fetch(window.BASE_URL + '/cart-update.php', {
          method: 'POST',
          body: fd
        }).then(res => res.json());

        if (!r.ok) return;

        /* Update the visible qty */
        const qtyEl = row.querySelector('.qty-value');
        if (qtyEl) qtyEl.textContent = newQty;

        /* Update the data attributes on both +/- buttons
           so the next click sends the correct value */
        const minus = row.querySelector('.qty-btn[aria-label="Decrease quantity"]');
        const plus  = row.querySelector('.qty-btn[aria-label="Increase quantity"]');
        if (minus) minus.dataset.qty = newQty - 1;
        if (plus)  plus.dataset.qty  = newQty + 1;

        /* Update the header badge */
        updateHeaderCount(r.count);

        /* Recompute summary from the DOM (now that qty changed) */
        refreshSummary();

        if (window.toast) window.toast('Cart updated');
      } catch (err) {
        if (window.toast) window.toast('Network error');
      }
      return;
    }

    /* =============================================================
       3) REMOVE ITEM
    ============================================================= */
    const removeBtn = e.target.closest('.cart-remove');
    if (removeBtn) {
      e.preventDefault();
      const row = removeBtn.closest('.cart-item');

      try {
        const fd = new FormData();
        fd.append('id', removeBtn.dataset.id);
        const r = await fetch(window.BASE_URL + '/cart-remove.php', {
          method: 'POST',
          body: fd
        }).then(res => res.json());

        /* Animate row away, then refresh summary */
        if (row) {
          row.style.transition = 'opacity .25s, transform .25s, max-height .3s';
          row.style.opacity    = '0';
          row.style.transform  = 'translateX(-24px)';
          row.style.maxHeight  = row.offsetHeight + 'px';

          setTimeout(() => {
            row.style.maxHeight = '0';
            row.style.padding   = '0';
            row.style.margin    = '0';
            row.style.border    = '0';
          }, 100);

          setTimeout(() => {
            row.remove();
            refreshSummary();
            handleEmptyCart();
          }, 450);
        }

        if (r && r.count !== undefined) updateHeaderCount(r.count);
        if (window.toast) window.toast('Item removed');
      } catch (err) {
        if (window.toast) window.toast('Network error');
      }
      return;
    }
  });

  /* ==================================================================
     On page load — initialize summary from real DOM values
  ================================================================== */
  document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.cart-item')) {
      refreshSummary();
    }
  });

})();