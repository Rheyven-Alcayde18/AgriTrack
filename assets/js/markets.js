// assets/js/markets.js
// Market locations: crop picker, filters, net-revenue ranking, saved markets and the Leaflet/OpenStreetMap map.
// Loaded by user/market-locations.php, which first sets window.AGRITRACK_DATA (markets + crops) from PHP and loads Leaflet.
(function () {
  var DATA = window.AGRITRACK_DATA || { crops: [], markets: [] };
  var DEFAULT_FARM = { name: 'Bay, Laguna, Philippines', lat: 14.1833, lon: 121.2833 };
  var SAVED_KEY = 'agritrack.savedMarkets';
  var TYPE_LABEL = { public: 'Public market', wholesale: 'Wholesale', coop: 'Cooperative' };
  var TYPE_COLOR = { public: '#1a9d5c', wholesale: '#e0a000', coop: '#3a86c8' };
  var DAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  var $ = function (id) { return document.getElementById(id); };
  var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var peso = function (n) { return '₱' + Math.round(n).toLocaleString('en-US'); };
  var cropById = {};
  DATA.crops.forEach(function (c) { cropById[c.id] = c; });

  // Use the same place as the Weather page when one has been chosen
  function getFarm() {
    try {
      var v = JSON.parse(localStorage.getItem('agritrack.location'));
      if (v && isFinite(v.lat) && isFinite(v.lon) && v.name) return v;
    } catch (e) {}
    return DEFAULT_FARM;
  }
  var farm = getFarm();
  $('fromName').textContent = farm.name;

  var saved = [];
  try { saved = JSON.parse(localStorage.getItem(SAVED_KEY)) || []; } catch (e) {}
  function persistSaved() { try { localStorage.setItem(SAVED_KEY, JSON.stringify(saved)); } catch (e) {} }

  var state = { crop: DATA.defaultCrop, q: '', type: 'all', km: 40, sort: 'net', rate: 15, onlyBuying: true, selected: null };

  function km(lat1, lon1, lat2, lon2) {
    var R = 6371, rad = Math.PI / 180;
    var dLat = (lat2 - lat1) * rad, dLon = (lon2 - lon1) * rad;
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  // Current day and time in Manila, so "open now" is right wherever the page is viewed
  function manilaNow() {
    var parts = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', weekday: 'short', hour: 'numeric', minute: 'numeric', hour12: false }).formatToParts(new Date());
    var o = {};
    parts.forEach(function (p) { o[p.type] = p.value; });
    return { day: DAY.indexOf(o.weekday), mins: (parseInt(o.hour, 10) % 24) * 60 + parseInt(o.minute, 10) };
  }
  function toMins(t) { var p = t.split(':'); return parseInt(p[0], 10) * 60 + parseInt(p[1], 10); }
  function isOpen(m) {
    var n = manilaNow(), f = toMins(m.from), t = toMins(m.to);
    if (f < t) return m.days.indexOf(n.day) > -1 && n.mins >= f && n.mins < t;
    var prev = (n.day + 6) % 7;
    return (m.days.indexOf(n.day) > -1 && n.mins >= f) || (m.days.indexOf(prev) > -1 && n.mins < t);
  }
  function clock(t) {
    var p = t.split(':'), h = parseInt(p[0], 10), ap = h >= 12 ? 'PM' : 'AM';
    return ((h + 11) % 12 + 1) + (p[1] !== '00' ? ':' + p[1] : '') + ' ' + ap;
  }
  function daysText(d) {
    if (d.length === 7) return 'Daily';
    if (d.length === 6 && d.indexOf(0) === -1) return 'Mon to Sat';
    return d.map(function (x) { return DAY[x]; }).join(', ');
  }

  function daysUntil(iso) {
    var a = new Date(); a.setHours(0, 0, 0, 0);
    var b = new Date(iso + 'T00:00:00');
    return Math.round((b - a) / 86400000);
  }

  function compute() {
    var crop = cropById[state.crop];
    return DATA.markets.map(function (m) {
      var dist = km(farm.lat, farm.lon, m.lat, m.lon);
      var price = m.prices[crop.id];
      var buys = price != null;
      var gross = buys ? crop.yield_t * 1000 * price : null;
      var trans = dist * 2 * state.rate;
      return {
        m: m, dist: dist, mins: Math.max(5, Math.round(dist * 2)), price: buys ? price : null,
        gross: gross, trans: trans, net: buys ? gross - trans : null, open: isOpen(m)
      };
    });
  }

  function visible(rows) {
    var q = state.q.trim().toLowerCase();
    return rows.filter(function (r) {
      if (r.dist > state.km) return false;
      if (state.onlyBuying && r.price == null) return false;
      if (state.type === 'saved') { if (saved.indexOf(r.m.id) === -1) return false; }
      else if (state.type !== 'all' && r.m.type !== state.type) return false;
      if (q && (r.m.name + ' ' + r.m.town).toLowerCase().indexOf(q) === -1) return false;
      return true;
    }).sort(function (a, b) {
      var an = a.price == null, bn = b.price == null;
      if (an !== bn) return an ? 1 : -1;
      if (state.sort === 'near' || (an && bn)) return a.dist - b.dist;
      if (state.sort === 'price') return b.price - a.price || a.dist - b.dist;
      return b.net - a.net;
    });
  }

  function renderSelling() {
    var c = cropById[state.crop], d = daysUntil(c.harvest);
    var when = d > 0 ? 'in ' + d + ' day' + (d === 1 ? '' : 's') : (d === 0 ? 'today' : 'ready now');
    $('selling').innerHTML = '<span>Selling <b>' + c.yield_t + ' t of ' + esc(c.name) + '</b> from ' + esc(c.field) +
      ' &middot; harvest ' + esc(c.harvest) + ' (' + when + ')</span>' +
      '<a href="harvest-management.php#crop-' + esc(c.id) + '">View in harvest plan &rarr;</a>';
  }

  function renderStats(list) {
    var buying = list.filter(function (r) { return r.price != null; });
    var nearest = list.slice().sort(function (a, b) { return a.dist - b.dist; })[0];
    var bestPrice = buying.slice().sort(function (a, b) { return b.price - a.price; })[0];
    var bestNet = buying.slice().sort(function (a, b) { return b.net - a.net; })[0];
    var cells = [
      ['Markets shown', String(list.length), buying.length + ' buy ' + cropById[state.crop].name.toLowerCase()],
      ['Nearest', nearest ? nearest.dist.toFixed(1) + ' km' : '--', nearest ? nearest.m.short : 'None in range'],
      ['Highest price', bestPrice ? peso(bestPrice.price) + '/kg' : '--', bestPrice ? bestPrice.m.short : 'No buyers in range'],
      ['Best net revenue', bestNet ? peso(bestNet.net) : '--', bestNet ? bestNet.m.short : 'No buyers in range']
    ];
    $('stats').innerHTML = cells.map(function (c) {
      return '<div class="ml-stat"><small>' + esc(c[0]) + '</small><b>' + esc(c[1]) + '</b><span>' + esc(c[2]) + '</span></div>';
    }).join('');
    return bestNet;
  }

  var STAR = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"/></svg>';

  function renderList(list, best) {
    var crop = cropById[state.crop];
    $('empty').style.display = list.length ? 'none' : 'block';
    $('list').innerHTML = list.map(function (r, i) {
      var m = r.m, isBest = best && r === best && r.price != null;
      var chips = Object.keys(m.prices).map(function (id) {
        return '<span class="ml-chip' + (id === state.crop ? ' on' : '') + '">' + esc(cropById[id].name) + ' ' + peso(m.prices[id]) + '</span>';
      }).join('');
      var money = r.price != null
        ? '<div class="ml-meta">' +
            '<div><small>Price paid</small><b>' + peso(r.price) + '/kg</b><span>Gross ' + peso(r.gross) + '</span></div>' +
            '<div><small>Transport</small><b>' + peso(r.trans) + '</b><span>Round trip, ' + (r.dist * 2).toFixed(0) + ' km</span></div>' +
            '<div class="net"><small>You would earn</small><b>' + peso(r.net) + '</b><span>After transport</span></div>' +
          '</div>'
        : '<p class="ml-nobuy">This market does not buy ' + esc(crop.name.toLowerCase()) + '.</p>';
      var isSaved = saved.indexOf(m.id) > -1;
      return '<article class="ml-card ml-m' + (r.price == null ? ' off' : '') + (state.selected === m.id ? ' sel' : '') + '" id="m-' + esc(m.id) + '" data-id="' + esc(m.id) + '">' +
        '<div class="ml-m-top">' +
          '<div class="ml-m-name"><span class="ml-rank">' + (i + 1) + '</span><div><b>' + esc(m.name) + '</b><small>' + esc(m.town) + ' &middot; ' + r.dist.toFixed(1) + ' km &middot; about ' + r.mins + ' min</small></div></div>' +
          '<button type="button" class="ml-star' + (isSaved ? ' on' : '') + '" data-save="' + esc(m.id) + '" aria-label="' + (isSaved ? 'Remove from saved' : 'Save this market') + '" aria-pressed="' + isSaved + '">' + STAR + '</button>' +
        '</div>' +
        '<div class="ml-tags">' +
          '<span class="ml-tag ' + esc(m.type) + '">' + esc(TYPE_LABEL[m.type]) + '</span>' +
          '<span class="ml-tag ' + (r.open ? 'open' : 'closed') + '">' + (r.open ? 'Open now' : 'Closed now') + '</span>' +
          (isBest ? '<span class="ml-tag best">Best net revenue</span>' : '') +
        '</div>' +
        money +
        '<p class="ml-note"><b>' + esc(daysText(m.days)) + ', ' + clock(m.from) + ' to ' + clock(m.to) + '.</b> ' + esc(m.note) + '</p>' +
        '<div class="ml-chips">' + chips + '</div>' +
        '<div class="ml-actions">' +
          '<a class="ml-btn primary" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=' + m.lat + ',' + m.lon + '">Get directions</a>' +
          '<a class="ml-btn" href="harvest-management.php#crop-' + esc(crop.id) + '">Plan harvest</a>' +
        '</div>' +
      '</article>';
    }).join('');
  }

  // Real street map: Leaflet with OpenStreetMap tiles
  var map = null, layer = null, markers = {}, lastFit = '', lastSel = null;

  function initMap() {
    if (map) return true;
    if (typeof L === 'undefined') return false;
    map = L.map('map', { scrollWheelZoom: false }).setView([farm.lat, farm.lon], 12);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
    }).addTo(map);
    layer = L.layerGroup().addTo(map);
    // Scroll-zoom only after the map has been clicked, so scrolling the page never gets stuck on it
    map.on('click', function () { map.scrollWheelZoom.enable(); });
    map.on('mouseout', function () { map.scrollWheelZoom.disable(); });
    return true;
  }

  function popupHtml(r) {
    var crop = cropById[state.crop], m = r.m;
    var money = r.price != null
      ? peso(r.price) + '/kg &middot; earn ' + peso(r.net)
      : 'Does not buy ' + esc(crop.name.toLowerCase());
    return '<div class="ml-pop"><b>' + esc(m.name) + '</b><br>' + esc(m.town) + ' &middot; ' + r.dist.toFixed(1) + ' km<br>' + money +
      '<br><a target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=' + m.lat + ',' + m.lon + '">Get directions</a></div>';
  }

  function selectMarket(id) {
    state.selected = id;
    render();
    var el = $('m-' + id);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function renderMap(list) {
    if (!initMap()) {
      $('map').innerHTML = '<p class="ml-mapfail">The map could not be loaded. Check your connection. The market list below still works.</p>';
      return;
    }
    layer.clearLayers();
    markers = {};
    var pts = [[farm.lat, farm.lon]];

    L.marker([farm.lat, farm.lon], {
      icon: L.divIcon({ className: '', html: '<div class="ml-farmpin"></div>', iconSize: [22, 22], iconAnchor: [11, 11] }),
      keyboard: false, zIndexOffset: 1000
    }).addTo(layer).bindTooltip('Your farm', { permanent: true, direction: 'top', offset: [0, -10], className: 'ml-tt' });

    list.forEach(function (r) {
      var m = r.m, sel = state.selected === m.id;
      var mk = L.circleMarker([m.lat, m.lon], {
        radius: sel ? 11 : 8, color: sel ? '#0c4a30' : '#ffffff', weight: sel ? 3 : 2,
        fillColor: TYPE_COLOR[m.type], fillOpacity: r.price == null ? 0.4 : 0.95
      }).addTo(layer);
      mk.bindTooltip(m.short, { permanent: true, direction: 'right', offset: [9, 0], className: 'ml-tt' });
      mk.bindPopup(popupHtml(r));
      mk.on('click', function () { selectMarket(m.id); });
      markers[m.id] = mk;
      pts.push([m.lat, m.lon]);
    });

    // Re-fit only when the set of visible markets changes, not on every click
    var key = pts.map(String).join('|');
    if (key !== lastFit) {
      map.fitBounds(L.latLngBounds(pts), { padding: [36, 36], maxZoom: 13 });
      lastFit = key;
    }
    var selMarker = state.selected && markers[state.selected];
    if (selMarker) {
      selMarker.bringToFront();
      selMarker.openPopup();
      if (state.selected !== lastSel) map.panTo(selMarker.getLatLng());
    }
    lastSel = state.selected;
  }

  function render() {
    renderSelling();
    var rows = compute();
    var list = visible(rows);
    var best = renderStats(list);
    renderList(list, best);
    renderMap(list);
  }

  // Controls
  DATA.crops.forEach(function (c) {
    var o = document.createElement('option');
    o.value = c.id; o.textContent = c.name + ' (' + c.field + ')';
    $('crop').appendChild(o);
  });
  $('crop').value = state.crop;
  $('crop').addEventListener('change', function () { state.crop = this.value; render(); });
  $('q').addEventListener('input', function () { state.q = this.value; render(); });
  $('sort').addEventListener('change', function () { state.sort = this.value; render(); });
  $('km').addEventListener('input', function () { state.km = +this.value; $('kmVal').textContent = this.value + ' km'; render(); });
  $('rate').addEventListener('input', function () { state.rate = Math.max(0, +this.value || 0); render(); });
  $('onlyBuying').addEventListener('change', function () { state.onlyBuying = this.checked; render(); });
  $('types').addEventListener('click', function (e) {
    var b = e.target.closest('.ml-pill'); if (!b) return;
    state.type = b.getAttribute('data-type');
    document.querySelectorAll('.ml-pill').forEach(function (p) { p.classList.toggle('active', p === b); });
    render();
  });

  // Save a market, or select one from the map
  document.addEventListener('click', function (e) {
    var star = e.target.closest('[data-save]');
    if (star) {
      var id = star.getAttribute('data-save'), i = saved.indexOf(id);
      if (i > -1) saved.splice(i, 1); else saved.push(id);
      persistSaved(); render(); return;
    }
    var cardEl = e.target.closest('.ml-m');
    if (cardEl && !e.target.closest('a, button')) {
      state.selected = cardEl.getAttribute('data-id');
      render();
    }
  });

  render();
})();