/* =========================================================
   AgriTrack theme.js  (dark mode toggle + glowing cursor)
   ========================================================= */

// ---- Dark mode toggle ----
(function () {
  var root = document.documentElement;
  var btn = document.getElementById('theme-toggle');
  if (!btn) return;
  var icon = btn.querySelector('.theme-icon');
  var label = btn.querySelector('.theme-label');

  function sync() {
    var dark = root.getAttribute('data-theme') === 'dark';
    if (icon) icon.className = 'ph-bold ' + (dark ? 'ph-sun' : 'ph-moon') + ' theme-icon';
    if (label) label.textContent = dark ? 'Light mode' : 'Dark mode';
    btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
  }
  sync();

  btn.addEventListener('click', function () {
    var dark = root.getAttribute('data-theme') === 'dark';
    if (dark) root.removeAttribute('data-theme');
    else root.setAttribute('data-theme', 'dark');
    try { localStorage.setItem('theme', dark ? 'light' : 'dark'); } catch (e) {}
    sync();
  });
})();


// ---- Glowing cursor (dark mode only, styles are injected here) ----
(function () {
  // no glow on touch screens
  if (window.matchMedia && window.matchMedia('(hover: none)').matches) return;

  // styles
  var style = document.createElement('style');
  style.textContent =
    '.cursor-aura,.cursor-dot{position:fixed;top:0;left:0;border-radius:50%;' +
    'pointer-events:none;z-index:2147483647;opacity:0;transition:opacity .25s;will-change:transform;}' +
    '.cursor-aura{width:340px;height:340px;mix-blend-mode:screen;' +
    'background:radial-gradient(circle,rgba(120,255,175,.45) 0%,rgba(60,220,120,.22) 30%,rgba(31,157,85,.08) 55%,transparent 72%);}' +
    '.cursor-dot{width:14px;height:14px;background:#d9ffe8;' +
    'box-shadow:0 0 12px 4px rgba(120,255,175,.9),0 0 32px 12px rgba(60,220,120,.55);}' +
    '[data-theme="dark"] .cursor-aura,[data-theme="dark"] .cursor-dot{opacity:1;}';
  document.head.appendChild(style);

  // elements
  var aura = document.createElement('div');
  aura.className = 'cursor-aura';
  var dot = document.createElement('div');
  dot.className = 'cursor-dot';
  document.body.appendChild(aura);
  document.body.appendChild(dot);

  // follow the mouse (aura trails smoothly, dot is exact)
  var mx = -500, my = -500, ax = -500, ay = -500;

  window.addEventListener('mousemove', function (e) {
    mx = e.clientX;
    my = e.clientY;
  });

  function loop() {
    ax += (mx - ax) * 0.18;
    ay += (my - ay) * 0.18;
    aura.style.transform = 'translate(' + ax + 'px,' + ay + 'px) translate(-50%,-50%)';
    dot.style.transform = 'translate(' + mx + 'px,' + my + 'px) translate(-50%,-50%)';
    requestAnimationFrame(loop);
  }
  loop();
})();