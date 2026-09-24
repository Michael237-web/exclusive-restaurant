<?php
/**
 * chatbot.php
 * Floating smart chatbot widget.
 * Include once from footer.php:
 *     <?php require __DIR__.'/chatbot.php'; ?>
 */

if (!isset($pdo)) {
    require_once __DIR__.'/db.php';
}
require_once __DIR__.'/functions.php';

$chat_site_name = setting($pdo, 'site_name', 'Restaurant');
?>

<style>
/* ============================================================
   CHATBOT — RESPONSIVE POSITIONING
   WhatsApp stays at bottom-right. Chatbot sits above it on
   desktop, and side-by-side with it on mobile.
   ============================================================ */

/* ---------- Launcher ---------- */
.chat-launcher{
  position:fixed;
  bottom:96px;      /* above WhatsApp on desktop */
  right:24px;
  z-index:2001;
  width:60px;height:60px;border-radius:50%;border:0;cursor:pointer;
  background:linear-gradient(135deg,#e67e22,#c96a12);
  color:#fff;box-shadow:0 12px 30px rgba(230,126,34,.45);
  display:grid;place-items:center;
  transition:transform .25s cubic-bezier(.22,.61,.36,1),box-shadow .25s;
}
.chat-launcher:hover{transform:scale(1.08);box-shadow:0 16px 36px rgba(230,126,34,.55)}
.chat-launcher svg{width:28px;height:28px}

.chat-launcher .chat-badge{
  position:absolute;top:-3px;right:-3px;
  width:16px;height:16px;border-radius:50%;
  background:#dc2626;border:2px solid #fff;
  animation:chatBadgePulse 1.8s infinite;
}
@keyframes chatBadgePulse{
  0%,100%{transform:scale(1);opacity:1}
  50%{transform:scale(1.2);opacity:.7}
}

/* ---------- Panel ---------- */
.chat-panel{
  position:fixed;
  bottom:172px;     /* launcher bottom(96) + launcher height(60) + gap(16) */
  right:24px;
  z-index:2001;
  width:380px;
  max-width:calc(100vw - 48px);
  height:600px;
  max-height:calc(100vh - 190px);
  background:#fff;border-radius:20px;
  box-shadow:0 24px 60px rgba(0,0,0,.25);
  display:flex;flex-direction:column;overflow:hidden;
  opacity:0;transform:translateY(20px) scale(.96);
  pointer-events:none;
  transition:opacity .25s,transform .25s;
}
.chat-panel.open{opacity:1;transform:none;pointer-events:auto}

/* ---------- Header ---------- */
.chat-head{
  background:linear-gradient(135deg,#e67e22,#c96a12);color:#fff;
  padding:14px 18px;display:flex;align-items:center;gap:12px;flex-shrink:0;
}
.chat-head-icon{
  width:40px;height:40px;border-radius:50%;
  background:rgba(255,255,255,.18);
  display:grid;place-items:center;flex-shrink:0;
}
.chat-head h3{font-size:1rem;font-weight:700;margin:0;letter-spacing:-.01em}
.chat-head small{
  font-size:.78rem;opacity:.92;
  display:flex;align-items:center;gap:6px;margin-top:2px;
}
.chat-status-dot{
  width:8px;height:8px;border-radius:50%;background:#4ade80;
  animation:chatStatusPulse 2s infinite;
}
@keyframes chatStatusPulse{0%,100%{opacity:1}50%{opacity:.4}}
.chat-head-close{
  margin-left:auto;background:rgba(255,255,255,.15);border:0;
  width:32px;height:32px;border-radius:50%;color:#fff;cursor:pointer;
  display:grid;place-items:center;transition:background .2s;
}
.chat-head-close:hover{background:rgba(255,255,255,.28)}

/* ---------- Body ---------- */
.chat-body{
  flex:1;overflow-y:auto;padding:18px 16px 8px;
  background:#fafafa;display:flex;flex-direction:column;gap:12px;
  -webkit-overflow-scrolling:touch;
}
.chat-msg{
  max-width:85%;padding:11px 15px;border-radius:16px;
  font-size:.92rem;line-height:1.5;word-wrap:break-word;
  animation:msgIn .25s cubic-bezier(.22,.61,.36,1);
  white-space:pre-line;
}
@keyframes msgIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
.chat-msg.bot{
  background:#fff;color:#222;border:1px solid #eee;
  align-self:flex-start;border-bottom-left-radius:6px;
}
.chat-msg.user{
  background:linear-gradient(135deg,#e67e22,#c96a12);color:#fff;
  align-self:flex-end;border-bottom-right-radius:6px;
}
.chat-msg strong{font-weight:700}
.chat-msg code{
  background:#f4f4f4;padding:1px 6px;border-radius:4px;
  font-family:ui-monospace,monospace;font-size:.88em;color:#b35a00;
}

/* ---------- Typing ---------- */
.chat-typing{
  align-self:flex-start;background:#fff;border:1px solid #eee;
  padding:12px 16px;border-radius:16px;border-bottom-left-radius:6px;
  display:flex;gap:4px;
}
.chat-typing span{
  width:7px;height:7px;border-radius:50%;background:#bbb;
  animation:typing 1.4s infinite;
}
.chat-typing span:nth-child(2){animation-delay:.15s}
.chat-typing span:nth-child(3){animation-delay:.3s}
@keyframes typing{
  0%,60%,100%{transform:translateY(0);opacity:.5}
  30%{transform:translateY(-5px);opacity:1}
}

/* ---------- Quick replies ---------- */
.chat-quick{
  display:flex;gap:8px;flex-wrap:wrap;padding:0 16px 10px;
  background:#fafafa;flex-shrink:0;
}
.chat-quick button{
  background:#fff;border:1px solid #e5e5e5;color:#333;
  padding:7px 13px;border-radius:20px;font-size:.8rem;cursor:pointer;
  transition:all .2s;
}
.chat-quick button:hover{
  background:#e67e22;color:#fff;border-color:#e67e22;
}

/* ---------- Input ---------- */
.chat-input{
  display:flex;gap:8px;padding:12px 14px;
  background:#fff;border-top:1px solid #eee;flex-shrink:0;
}
.chat-input input{
  flex:1;border:1px solid #e5e5e5;border-radius:24px;
  padding:11px 18px;font:inherit;font-size:.92rem;
  outline:none;transition:border-color .2s;margin:0;
}
.chat-input input:focus{border-color:#e67e22}
.chat-input button{
  width:44px;height:44px;border-radius:50%;border:0;
  background:linear-gradient(135deg,#e67e22,#c96a12);color:#fff;
  cursor:pointer;display:grid;place-items:center;flex-shrink:0;
  transition:transform .2s,box-shadow .2s;
}
.chat-input button:hover:not(:disabled){
  transform:scale(1.05);box-shadow:0 6px 18px rgba(230,126,34,.4);
}
.chat-input button:disabled{opacity:.5;cursor:not-allowed}

/* ============================================================
   RESPONSIVE
   ============================================================ */

/* Tablet (≤ 900px) */
@media (max-width:900px){
  .chat-panel{
    width:340px;
    max-width:calc(100vw - 40px);
    height:520px;
    max-height:calc(100vh - 180px);
    bottom:164px;right:20px;
  }
  .chat-launcher{bottom:92px;right:20px}
}

/* Mobile (≤ 600px) — FULL SCREEN OVERLAY */
@media (max-width:600px){
  .chat-panel{
    position:fixed;
    top:0;left:0;right:0;bottom:0;
    width:100%;height:100%;
    max-width:100%;max-height:100%;
    border-radius:0;
    padding-top:env(safe-area-inset-top, 0);
    padding-bottom:env(safe-area-inset-bottom, 0);
    transform:translateY(100%);
    transition:transform .3s cubic-bezier(.22,.61,.36,1);
  }
  .chat-panel.open{transform:translateY(0)}

  /* Side-by-side with WhatsApp — WhatsApp is at right:80px on mobile */
  .chat-launcher{
    width:54px;height:54px;
    bottom:24px;
    right:16px;
  }
  .chat-launcher svg{width:24px;height:24px}

  .chat-head{padding:14px 16px}
  .chat-head-icon{width:38px;height:38px}
  .chat-body{padding:16px 14px 6px}
  .chat-msg{max-width:90%;font-size:.9rem}
  .chat-quick{padding:0 14px 10px}
  .chat-quick button{font-size:.75rem;padding:6px 11px}
  .chat-input{padding:10px 12px}
  .chat-input input{padding:10px 16px;font-size:.9rem}
  .chat-input button{width:40px;height:40px}
}

/* Very small phones (≤ 380px) */
@media (max-width:380px){
  .chat-launcher{width:50px;height:50px;bottom:22px;right:14px}
  .chat-launcher svg{width:22px;height:22px}
  .chat-head{padding:12px 14px}
  .chat-head-icon{width:34px;height:34px}
  .chat-head h3{font-size:.9rem}
  .chat-head small{font-size:.7rem}
}

/* Landscape phones (short viewport) */
@media (max-height:500px) and (max-width:900px){
  .chat-panel{
    top:0;bottom:0;
    height:100%;max-height:100%;
    border-radius:0;
    transform:translateY(100%);
  }
  .chat-panel.open{transform:translateY(0)}
}

/* ---------- Dark mode ---------- */
body.dark .chat-panel{background:#1a1d22}
body.dark .chat-body{background:#15171b}
body.dark .chat-msg.bot{background:#232830;color:#e6e6e6;border-color:#2d333c}
body.dark .chat-msg code{background:#2d333c;color:#fbbf24}
body.dark .chat-input{background:#1a1d22;border-color:#2d333c}
body.dark .chat-input input{background:#0f1115;color:#e6e6e6;border-color:#2d333c}
body.dark .chat-quick{background:#15171b}
body.dark .chat-quick button{background:#232830;color:#ddd;border-color:#2d333c}
body.dark .chat-quick button:hover{background:#e67e22;color:#fff}
</style>

<button class="chat-launcher" id="chatLauncher" aria-label="Open chat">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
       stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
  </svg>
  <span class="chat-badge"></span>
</button>

<div class="chat-panel" id="chatPanel" role="dialog" aria-label="Chat with us">
  <div class="chat-head">
    <div class="chat-head-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"/>
        <path d="M8 9h.01M16 9h.01M8 14s2 2 4 2 4-2 4-2"/>
      </svg>
    </div>
    <div>
      <h3><?= e($chat_site_name) ?></h3>
      <small><span class="chat-status-dot"></span> Online — how can I help?</small>
    </div>
    <button class="chat-head-close" id="chatClose" aria-label="Close chat">✕</button>
  </div>

  <div class="chat-body" id="chatBody"></div>

  <div class="chat-quick" id="chatQuick">
    <button data-q="What time do you open?">🕐 Hours</button>
    <button data-q="Show me pizza">🍕 Pizza</button>
    <button data-q="Any offers?">🎁 Offers</button>
    <button data-q="Do you deliver?">🚚 Delivery</button>
  </div>

  <form class="chat-input" id="chatForm" autocomplete="off">
    <input type="text" id="chatInput" placeholder="Ask about any dish…" aria-label="Message">
    <button type="submit" id="chatSend" aria-label="Send">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m22 2-7 20-4-9-9-4z"/>
        <path d="M22 2 11 13"/>
      </svg>
    </button>
  </form>
</div>

<script>
(function () {
  'use strict';

  const launcher = document.getElementById('chatLauncher');
  const panel    = document.getElementById('chatPanel');
  const closeBtn = document.getElementById('chatClose');
  const body     = document.getElementById('chatBody');
  const form     = document.getElementById('chatForm');
  const input    = document.getElementById('chatInput');
  const sendBtn  = document.getElementById('chatSend');
  const quickBox = document.getElementById('chatQuick');

  if (!launcher || !panel) return;

  /* Endpoint path — uses clean URL, .htaccess rewrites to chatbot-api.php */
  const BASE = window.BASE_URL || '';
  const API  = BASE + '/chatbot-api';

  let greeted = false;
  let busy    = false;

  /* ---------- Open / close ---------- */
  function openPanel() {
    panel.classList.add('open');
    launcher.style.display = 'none';
    if (!greeted) { greet(); greeted = true; }
    setTimeout(() => input.focus(), 250);
  }
  function closePanel() {
    panel.classList.remove('open');
    launcher.style.display = '';
  }
  launcher.addEventListener('click', openPanel);
  closeBtn.addEventListener('click', closePanel);
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && panel.classList.contains('open')) closePanel();
  });

  /* ---------- Formatting ---------- */
  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }
  function formatMessage(text) {
    let html = escapeHtml(text);
    /* Bold **text** */
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    /* Inline code `text` */
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
    /* Preserve line breaks */
    html = html.replace(/\n/g, '<br>');
    return html;
  }

  function addMsg(text, who) {
    const div = document.createElement('div');
    div.className = 'chat-msg ' + who;
    div.innerHTML = formatMessage(text);
    body.appendChild(div);
    body.scrollTop = body.scrollHeight;
  }

  function showTyping() {
    const t = document.createElement('div');
    t.className = 'chat-typing';
    t.id = 'chatTyping';
    t.innerHTML = '<span></span><span></span><span></span>';
    body.appendChild(t);
    body.scrollTop = body.scrollHeight;
  }
  function hideTyping() {
    const t = document.getElementById('chatTyping');
    if (t) t.remove();
  }

  /* ---------- Greeting ---------- */
  function greet() {
    const hour = new Date().getHours();
    let g = 'Hello';
    if (hour < 12)      g = 'Good morning';
    else if (hour < 17) g = 'Good afternoon';
    else                g = 'Good evening';

    addMsg(
      g + '! 👋 Welcome to our restaurant.\n\n' +
      'I can help with menu items, prices, availability, offers, delivery, and more.\n\n' +
      'Try asking: "pizza", "how much is a burger?", or "do you deliver?"',
      'bot'
    );
  }

  /* ---------- Send ---------- */
  async function send(text) {
    text = (text || '').trim();
    if (!text || busy) return;

    busy = true;
    sendBtn.disabled = true;
    input.value = '';

    addMsg(text, 'user');
    showTyping();

    try {
      const res = await fetch(API, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ message: text })
      });

      const raw = await res.text();

      /* Debug — remove in production */
      console.log('[chatbot] HTTP ' + res.status + ' →', raw);

      if (!res.ok) {
        hideTyping();
        addMsg("⚠️ Server error (" + res.status + "). Please try again.", 'bot');
        return;
      }

      let data;
      try { data = JSON.parse(raw); }
      catch (e) {
        hideTyping();
        addMsg("⚠️ Server sent unexpected data. Check the browser console.", 'bot');
        return;
      }

      hideTyping();
      addMsg(data.reply || "Sorry, I didn't catch that.", 'bot');

    } catch (err) {
      console.error('[chatbot] fetch failed:', err);
      hideTyping();
      addMsg("⚠️ Connection error. Please try again.", 'bot');
    } finally {
      busy = false;
      sendBtn.disabled = false;
      input.focus();
    }
  }

  form.addEventListener('submit', e => {
    e.preventDefault();
    send(input.value);
  });

  /* ---------- Quick replies ---------- */
  if (quickBox) {
    quickBox.addEventListener('click', e => {
      const btn = e.target.closest('button[data-q]');
      if (btn) send(btn.dataset.q);
    });
  }
})();
</script>
<!-- ============================== /CHATBOT ============================== -->