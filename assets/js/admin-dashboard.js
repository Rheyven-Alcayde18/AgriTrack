// assets/js/admin-dashboard.js
// Admin dashboard: chart tooltips, "Table" view toggles and live weather alerts.
// Loaded by admin/dashboard.php, which first sets window.AGRITRACK_ADMIN = { towns: [{name, lat, lon, farmers}] }.
(function () {
  var DATA = window.AGRITRACK_ADMIN || { towns: [] };
  var $ = function (id) { return document.getElementById(id); };
  var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };

  /* ---------- Tooltips (mouse and keyboard focus) ---------- */
  var tip = $('tip');
  function showTip(el, x, y) {
    var text = el.getAttribute('data-tip');
    if (!text) return;
    var lines = text.split('\n');
    tip.innerHTML = '<b>' + esc(lines[0]) + '</b>' + esc(lines.slice(1).join('\n'));
    tip.style.display = 'block';
    var w = tip.offsetWidth, h = tip.offsetHeight;
    var left = Math.min(Math.max(8, x - w / 2), window.innerWidth - w - 8);
    var top = y - h - 14;
    if (top < 8) top = y + 18;
    tip.style.left = left + 'px'; tip.style.top = top + 'px';
  }
  function hideTip() { tip.style.display = 'none'; }
  document.addEventListener('mousemove', function (e) {
    var el = e.target.closest ? e.target.closest('[data-tip]') : null;
    if (el) showTip(el, e.clientX, e.clientY); else hideTip();
  });
  document.addEventListener('focusin', function (e) {
    var el = e.target.closest ? e.target.closest('[data-tip]') : null;
    if (!el) return;
    var r = el.getBoundingClientRect();
    showTip(el, r.left + r.width / 2, r.top);
  });
  document.addEventListener('focusout', hideTip);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideTip(); });

  /* ---------- Chart <-> table toggles ---------- */
  document.querySelectorAll('[data-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-toggle');
      var table = $(id), viz = $(id + '-viz');
      var on = btn.getAttribute('aria-pressed') !== 'true';
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      btn.textContent = on ? 'Chart' : 'Table';
      table.classList.toggle('show', on);
      viz.classList.toggle('hide', on);
      hideTip();
    });
  });

  /* ---------- Weather alerts ---------- */
  var DAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  function dayName(iso, i) {
    return i === 0 ? 'today' : DAY[new Date(iso + 'T00:00:00').getDay()];
  }
  function townAlerts(t, d) {
    var out = [];
    for (var i = 0; i < d.time.length; i++) {
      var day = dayName(d.time[i], i), rain = d.precipitation_probability_max[i] || 0, wind = d.wind_speed_10m_max[i] || 0;
      if (d.weather_code[i] >= 95) out.push({ level: 'alert', title: 'Thunderstorms', day: day, text: 'Thunderstorms forecast. Open fields are unsafe during lightning.' });
      else if (rain >= 70) out.push({ level: 'warn', title: 'Heavy rain likely', day: day, text: rain + '% chance of rain. Spraying and fertilizing should wait.' });
      if (wind >= 30) out.push({ level: 'warn', title: 'Strong winds', day: day, text: 'Gusts around ' + Math.round(wind) + ' km/h can damage tall crops and covers.' });
      if (d.temperature_2m_max[i] >= 35) out.push({ level: 'warn', title: 'Extreme heat', day: day, text: 'High of ' + Math.round(d.temperature_2m_max[i]) + '°C. Plants and workers need extra water and shade.' });
    }
    return out;
  }

  function renderAlerts(results) {
    var items = [];
    results.forEach(function (r) {
      townAlerts(r.town, r.daily).forEach(function (a) { a.town = r.town; items.push(a); });
    });
    var box = $('alerts');
    if (!items.length) {
      box.innerHTML = '<p class="ad-wx-msg">No weather alerts for the next 3 days across ' + DATA.towns.length + ' municipalit' + (DATA.towns.length === 1 ? 'y' : 'ies') + '.</p>';
      return;
    }
    var rank = { alert: 0, warn: 1 };
    items.sort(function (a, b) { return rank[a.level] - rank[b.level]; });
    box.innerHTML = items.slice(0, 6).map(function (a) {
      return '<div class="ad-alert ' + a.level + '"><span class="ic">' + (a.level === 'alert' ? '!' : '▲') + '</span>' +
        '<div><b>' + esc(a.title) + ' in ' + esc(a.town.name) + ' (' + esc(a.day) + ')</b><span>' + esc(a.text) + '</span>' +
        '<small>' + a.town.farmers + ' active farmer' + (a.town.farmers === 1 ? '' : 's') + ' affected</small></div></div>';
    }).join('') + (items.length > 6 ? '<p class="ad-wx-msg">+ ' + (items.length - 6) + ' more alerts</p>' : '');
  }

  function loadAlerts() {
    var towns = DATA.towns, box = $('alerts');
    if (!towns.length) { box.innerHTML = '<p class="ad-wx-msg">No active farmers with a known location.</p>'; return; }
    var url = 'https://api.open-meteo.com/v1/forecast?latitude=' + towns.map(function (t) { return t.lat; }).join(',') +
      '&longitude=' + towns.map(function (t) { return t.lon; }).join(',') +
      '&daily=weather_code,temperature_2m_max,precipitation_probability_max,wind_speed_10m_max&timezone=auto&forecast_days=3';
    fetch(url)
      .then(function (r) { if (!r.ok) throw new Error('bad response'); return r.json(); })
      .then(function (data) {
        var arr = Array.isArray(data) ? data : [data];
        renderAlerts(arr.map(function (d, i) { return { town: towns[i], daily: d.daily }; }));
      })
      .catch(function () {
        box.innerHTML = '<p class="ad-wx-msg">Weather alerts could not be loaded. Check your connection and reload the page.</p>';
      });
  }
  loadAlerts();
})();