/* =========================================================
   AgriTrack chatbot.js
   Floating AI chat button (bottom right) for the whole website.
   Self-contained: it injects its own styles and HTML.
   Talks to /api/chat.php (path is worked out from this file's location).
   ========================================================= */
(function () {
  if (document.getElementById('agri-chat-fab')) return;

  // endpoint: assets/js/chatbot.js  ->  ../../api/chat.php  (project root)
  var script = document.currentScript;
  // Node server route (server.mjs). Change the port here if you changed PORT in .env.
  // Optional override:  <script src="..." data-endpoint="https://your-site.com/api/chat"></script>
  var ENDPOINT = (script && script.getAttribute('data-endpoint')) || 'http://localhost:3000/api/chat';

  var STORE_KEY = 'agri_chat_history';
  var history = [];
  try { history = JSON.parse(sessionStorage.getItem(STORE_KEY) || '[]'); } catch (e) { history = []; }

  // ---- styles ----
  var css = '' +
    '#agri-chat-fab{position:fixed;right:24px;bottom:24px;z-index:2147483000;width:58px;height:58px;border:0;border-radius:50%;' +
    'display:grid;place-items:center;cursor:pointer;color:#fff;background:linear-gradient(145deg,#1f9d55,#0b3d26);' +
    'box-shadow:0 10px 28px rgba(11,61,38,.45);transition:transform .2s,box-shadow .2s;}' +
    '#agri-chat-fab:hover{transform:translateY(-2px) scale(1.05);box-shadow:0 14px 34px rgba(11,61,38,.55);}' +
    '#agri-chat-fab svg{width:28px;height:28px;}' +
    '#agri-chat-fab .x{display:none;}' +
    '#agri-chat-fab.open .x{display:block;}#agri-chat-fab.open .chat{display:none;}' +

    '#agri-chat{position:fixed;right:24px;bottom:96px;z-index:2147483000;width:370px;max-width:calc(100vw - 32px);' +
    'height:540px;max-height:calc(100vh - 120px);display:none;flex-direction:column;overflow:hidden;' +
    'background:#fff;border:1px solid #d7e8d6;border-radius:20px;box-shadow:0 24px 60px rgba(0,0,0,.28);' +
    'font-family:inherit;color:#12382a;}' +
    '#agri-chat.open{display:flex;animation:agriPop .2s ease;}' +
    '@keyframes agriPop{from{opacity:0;transform:translateY(10px) scale(.98);}to{opacity:1;transform:none;}}' +

    '#agri-chat .hd{display:flex;align-items:center;gap:12px;padding:14px 16px;color:#fff;background:linear-gradient(145deg,#1f9d55,#0b3d26);}' +
    '#agri-chat .av{width:38px;height:38px;border-radius:12px;background:rgba(255,255,255,.18);display:grid;place-items:center;flex:none;}' +
    '#agri-chat .av svg{width:22px;height:22px;}' +
    '#agri-chat .ti{flex:1;min-width:0;line-height:1.2;}' +
    '#agri-chat .ti b{display:block;font-size:1rem;}' +
    '#agri-chat .ti small{opacity:.85;font-size:.76rem;}' +
    '#agri-chat .hb{border:0;background:rgba(255,255,255,.15);color:#fff;width:32px;height:32px;border-radius:10px;cursor:pointer;font-size:1rem;}' +
    '#agri-chat .hb:hover{background:rgba(255,255,255,.28);}' +

    '#agri-chat .ms{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;background:#f6faf4;}' +
    '#agri-chat .m{max-width:84%;padding:10px 14px;border-radius:16px;font-size:.92rem;line-height:1.45;word-wrap:break-word;overflow-wrap:anywhere;}' +
    '#agri-chat .m.bot{align-self:flex-start;background:#fff;border:1px solid #d7e8d6;border-bottom-left-radius:4px;}' +
    '#agri-chat .m.me{align-self:flex-end;color:#fff;background:linear-gradient(145deg,#1f9d55,#12713c);border-bottom-right-radius:4px;}' +
    '#agri-chat .m.err{align-self:flex-start;background:#fdf0ee;border:1px solid #f3c9c4;color:#8f261f;}' +
    '#agri-chat .dots{display:inline-flex;gap:4px;padding:2px 0;}' +
    '#agri-chat .dots i{width:7px;height:7px;border-radius:50%;background:#7a9a86;animation:agriDot 1.2s infinite ease-in-out;}' +
    '#agri-chat .dots i:nth-child(2){animation-delay:.15s;}#agri-chat .dots i:nth-child(3){animation-delay:.3s;}' +
    '@keyframes agriDot{0%,80%,100%{transform:scale(.6);opacity:.5;}40%{transform:scale(1);opacity:1;}}' +

    '#agri-chat .sg{display:flex;flex-wrap:wrap;gap:6px;margin-top:4px;}' +
    '#agri-chat .sg button{border:1px solid #bfdcc6;background:#fff;color:#12713c;font:inherit;font-size:.8rem;font-weight:600;' +
    'padding:6px 12px;border-radius:99px;cursor:pointer;}' +
    '#agri-chat .sg button:hover{background:#e4f4e1;}' +

    '#agri-chat form{display:flex;gap:8px;padding:12px;border-top:1px solid #d7e8d6;background:#fff;}' +
    '#agri-chat input{flex:1;min-width:0;border:1px solid #d7e8d6;border-radius:12px;padding:11px 14px;font:inherit;font-size:.92rem;' +
    'background:#f6faf4;color:#12382a;outline:none;}' +
    '#agri-chat input:focus{border-color:#1f9d55;box-shadow:0 0 0 3px rgba(31,157,85,.18);}' +
    '#agri-chat .send{border:0;width:44px;border-radius:12px;cursor:pointer;color:#fff;background:linear-gradient(145deg,#1f9d55,#0b3d26);display:grid;place-items:center;}' +
    '#agri-chat .send:disabled{opacity:.5;cursor:not-allowed;}' +
    '#agri-chat .send svg{width:20px;height:20px;}' +
    '#agri-chat .ft{text-align:center;font-size:.7rem;color:#7a8f81;padding:0 12px 10px;background:#fff;}' +

    /* dark mode */
    '[data-theme="dark"] #agri-chat{background:#12291d;border-color:#1f4030;color:#e6f4ea;}' +
    '[data-theme="dark"] #agri-chat .ms{background:#0a1a12;}' +
    '[data-theme="dark"] #agri-chat .m.bot{background:#12291d;border-color:#1f4030;color:#dbe8df;}' +
    '[data-theme="dark"] #agri-chat .m.err{background:rgba(214,69,61,.14);border-color:rgba(214,69,61,.35);color:#ffb4ad;}' +
    '[data-theme="dark"] #agri-chat .sg button{background:#12291d;border-color:#1f4030;color:#6fdc9a;}' +
    '[data-theme="dark"] #agri-chat .sg button:hover{background:#1a3d2a;}' +
    '[data-theme="dark"] #agri-chat form,[data-theme="dark"] #agri-chat .ft{background:#12291d;border-color:#1f4030;}' +
    '[data-theme="dark"] #agri-chat input{background:#0e2218;border-color:#1f4030;color:#e6f4ea;}' +
    '[data-theme="dark"] #agri-chat input::placeholder{color:#6f8a78;}' +
    '[data-theme="dark"] #agri-chat .ft{color:#7a9a86;}' +

    '@media (max-width:520px){#agri-chat{right:12px;left:12px;width:auto;max-width:none;bottom:88px;height:calc(100vh - 110px);}' +
    '#agri-chat-fab{right:16px;bottom:16px;}}';

  var style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);

  // ---- html ----
  var ICON_CHAT = '<svg class="chat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-4.6A8 8 0 1 1 21 12Z"/><path d=""/></svg>';
  var ICON_X = '<svg class="x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>';
  var ICON_LEAF = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18c0-8 5-13 13-13 0 8-5 13-13 13Z"/><path d="M6 18 14 10"/></svg>';
  var ICON_SEND = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

  var fab = document.createElement('button');
  fab.id = 'agri-chat-fab';
  fab.type = 'button';
  fab.setAttribute('aria-label', 'Open AgriTrack assistant');
  fab.setAttribute('aria-expanded', 'false');
  fab.innerHTML = ICON_CHAT + ICON_X;

  var panel = document.createElement('div');
  panel.id = 'agri-chat';
  panel.setAttribute('role', 'dialog');
  panel.setAttribute('aria-label', 'AgriTrack assistant');
  panel.innerHTML =
    '<div class="hd"><div class="av">' + ICON_LEAF + '</div>' +
    '<div class="ti"><b>AgriTrack Assistant</b><small>Ask me about farming and AgriTrack</small></div>' +
    '<button class="hb" type="button" data-act="clear" title="New chat" aria-label="Start a new chat">&#8635;</button>' +
    '<button class="hb" type="button" data-act="close" title="Close" aria-label="Close chat">&times;</button></div>' +
    '<div class="ms" id="agri-chat-ms" aria-live="polite"></div>' +
    '<form id="agri-chat-form" autocomplete="off">' +
    '<input id="agri-chat-input" type="text" maxlength="500" placeholder="Type your question..." aria-label="Your message">' +
    '<button class="send" type="submit" aria-label="Send">' + ICON_SEND + '</button></form>' +
    '<div class="ft">AI answers can be wrong. Double-check important decisions.</div>';

  document.body.appendChild(panel);
  document.body.appendChild(fab);

  var ms = panel.querySelector('#agri-chat-ms');
  var form = panel.querySelector('#agri-chat-form');
  var input = panel.querySelector('#agri-chat-input');
  var sendBtn = panel.querySelector('.send');
  var busy = false;

  // ---- helpers ----
  function fmt(t) {
    var s = String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    s = s.replace(/^\s*[\*\-]\s+(.+)$/gm, '&bull; $1');
    s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    return s.replace(/\n/g, '<br>');
  }
  function scrollDown() { ms.scrollTop = ms.scrollHeight; }
  function save() {
    try { sessionStorage.setItem(STORE_KEY, JSON.stringify(history.slice(-30))); } catch (e) {}
  }
  function addMsg(kind, text, isHtml) {
    var d = document.createElement('div');
    d.className = 'm ' + kind;
    if (isHtml) d.innerHTML = text; else d.textContent = text;
    ms.appendChild(d);
    scrollDown();
    return d;
  }
  function renderBot(text) { return addMsg('bot', fmt(text), true); }

  function welcome() {
    renderBot("Hi! I'm the AgriTrack Assistant. Ask me about crops, pests, weather planning, harvest timing, or how to use AgriTrack.");
    var sg = document.createElement('div');
    sg.className = 'sg';
    ['How do I add a crop?', 'When should I harvest rice?', 'How to protect crops from heavy rain?'].forEach(function (q) {
      var b = document.createElement('button');
      b.type = 'button';
      b.textContent = q;
      b.addEventListener('click', function () { sg.remove(); send(q); });
      sg.appendChild(b);
    });
    ms.appendChild(sg);
  }

  function restore() {
    ms.innerHTML = '';
    if (!history.length) { welcome(); return; }
    history.forEach(function (m) {
      if (m.role === 'user') addMsg('me', m.text);
      else renderBot(m.text);
    });
  }

  // ---- sending ----
  function send(text) {
    text = String(text || '').trim();
    if (!text || busy) return;

    busy = true;
    sendBtn.disabled = true;
    input.value = '';

    var sg = ms.querySelector('.sg');
    if (sg) sg.remove();

    addMsg('me', text);
    history.push({ role: 'user', text: text });
    save();

    var typing = addMsg('bot', '<span class="dots"><i></i><i></i><i></i></span>', true);

    fetch(ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: text, messages: history.slice(-12) })
    })
      .then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (data) {
          if (!res.ok || !data.reply) {
            throw new Error(data.error || ('Could not get a reply (HTTP ' + res.status + '). Check that api/chat.php exists and that PHP is running.'));
          }
          return data.reply;
        });
      })
      .then(function (reply) {
        typing.remove();
        renderBot(reply);
        history.push({ role: 'model', text: reply });
        save();
      })
      .catch(function (err) {
        typing.remove();
        // drop the unanswered user message so the history stays valid
        history.pop();
        save();
        addMsg('err', err && err.message ? err.message : 'Network error. Please try again.');
      })
      .then(function () {
        busy = false;
        sendBtn.disabled = false;
        input.focus();
      });
  }

  // ---- open / close ----
  function setOpen(open) {
    panel.classList.toggle('open', open);
    fab.classList.toggle('open', open);
    fab.setAttribute('aria-expanded', open ? 'true' : 'false');
    fab.setAttribute('aria-label', open ? 'Close AgriTrack assistant' : 'Open AgriTrack assistant');
    if (open) { scrollDown(); setTimeout(function () { input.focus(); }, 50); }
  }

  fab.addEventListener('click', function () { setOpen(!panel.classList.contains('open')); });

  panel.addEventListener('click', function (e) {
    var act = e.target.getAttribute && e.target.getAttribute('data-act');
    if (act === 'close') setOpen(false);
    if (act === 'clear') { history = []; save(); restore(); }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && panel.classList.contains('open')) setOpen(false);
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    send(input.value);
  });

  restore();
})();