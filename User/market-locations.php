<?php
$icons = [
  'home'    => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
  'crop'    => '<path d="M7 20h10"/><path d="M12 20v-9"/><path d="M12 11c0-3 2-5 6-5 0 4-2 6-6 5Z"/><path d="M12 13c0-2.5-1.8-4.5-5.5-4.5 0 3.5 2 5 5.5 4.5Z"/>',
  'weather' => '<path d="M17.5 19a4.5 4.5 0 1 0-1-8.9A6 6 0 0 0 4.7 12 3.5 3.5 0 0 0 6.5 19Z"/>',
  'harvest' => '<path d="M3 9h18l-2 11H5Z"/><path d="M8 9l3-5"/><path d="M16 9l-3-5"/>',
  'market'  => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
  'tips'    => '<path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.7.6 1 1.3 1 2.1h5c0-.8.3-1.5 1-2.1A6 6 0 0 0 12 3Z"/>',
  'leaf'    => '<path d="M6 18c0-8 5-13 13-13 0 8-5 13-13 13Z"/><path d="M6 18 14 10"/>',
  'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
];
function ic($k) { global $icons; return '<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">' . $icons[$k] . '</svg>'; }

$nav = [
  ['dashboard.php', 'Dashboard', 'home'],
  ['crop-monitoring.php', 'Crop monitoring', 'crop'],
  ['weather-forecast.php', 'Weather forecast', 'weather'],
  ['harvest-management.php', 'Harvest management', 'harvest'],
  ['market-locations.php', 'Market locations', 'market'],
  ['recommendations.php', 'Recommendations', 'tips'],
];
$current = 'market-locations.php';

// Your crops (shared with Crop monitoring and Harvest management)
require __DIR__ . '/crops-data.php';

// Market listings are shared with Recommendations (edit them in markets-data.php)
require __DIR__ . '/markets-data.php';

$cropOptions = [];
foreach ($crops as $c) {
  $cropOptions[] = ['id' => $c['id'], 'name' => $c['name'], 'field' => $c['field'], 'yield_t' => $c['yield_t'], 'harvest' => $c['harvest']];
}
// Open with ?crop=tomato to preselect a crop; otherwise the crop that is harvested first
$byHarvest = $crops;
usort($byHarvest, function ($a, $b) { return strcmp($a['harvest'], $b['harvest']); });
$default = $byHarvest[0]['id'];
if (isset($_GET['crop'])) {
  foreach ($crops as $c) { if ($c['id'] === $_GET['crop']) $default = $c['id']; }
}
$payload = json_encode(['markets' => $markets, 'crops' => $cropOptions, 'defaultCrop' => $default],
  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriTrack | Market locations</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <style>
    /* Market page only. Every class is prefixed ml- so nothing clashes with dashboard.css */
    .ml {
      --ink: #12382a; --dark: #0c4a30; --green: #1a9d5c; --mint: #eef7f1;
      --line: #e1eee6; --muted: #6b8a7a; --amber: #e0a000; --blue: #3a86c8;
      color: var(--ink); font-variant-numeric: tabular-nums;
    }
    .ml * { box-sizing: border-box; }
    .ml-head { margin-bottom: 20px; }
    .ml-head h1 { margin: 0 0 4px; }
    .ml-head p { margin: 0; color: var(--muted); }
    .ml-head .ml-welcome:empty { display: none; }
    .ml-from { display: inline-flex; align-items: center; gap: 8px; margin-top: 12px; padding: 7px 14px; border-radius: 999px; background: var(--mint); font-size: .84rem; color: var(--dark); font-weight: 600; }
    .ml-from svg { width: 16px; height: 16px; fill: none; stroke: var(--green); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .ml-from a { color: var(--green); font-weight: 700; }

    .ml-card { background: #fff; border: 1px solid var(--line); border-radius: 20px; }

    /* Controls */
    .ml-controls { padding: 20px 22px; margin-bottom: 16px; display: grid; gap: 16px; }
    .ml-row { display: flex; flex-wrap: wrap; gap: 14px 18px; align-items: flex-end; }
    .ml-field { display: flex; flex-direction: column; gap: 6px; min-width: 150px; }
    .ml-field.grow { flex: 1; min-width: 200px; }
    .ml-field label { font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); display: flex; justify-content: space-between; gap: 8px; }
    .ml-field label b { color: var(--dark); letter-spacing: 0; text-transform: none; font-size: .8rem; }
    .ml select, .ml input[type="text"], .ml input[type="number"] {
      font: inherit; font-size: .92rem; padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px; background: #fff; color: var(--ink); outline: none; width: 100%;
    }
    .ml select:focus, .ml input[type="text"]:focus, .ml input[type="number"]:focus { border-color: var(--green); box-shadow: 0 0 0 4px rgba(26,157,92,.12); }
    .ml input[type="range"] { accent-color: var(--green); width: 100%; margin: 8px 0 2px; }
    .ml-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .ml-pill {
      font: inherit; font-size: .84rem; font-weight: 700; cursor: pointer; padding: 8px 15px; border-radius: 999px;
      background: #fff; color: var(--muted); border: 1px solid var(--line); transition: all .15s;
    }
    .ml-pill:hover { color: var(--dark); border-color: var(--green); }
    .ml-pill.active { background: var(--dark); color: #fff; border-color: var(--dark); }
    .ml-check { display: flex; align-items: center; gap: 8px; font-size: .88rem; color: var(--ink); cursor: pointer; padding-bottom: 10px; }
    .ml-check input { width: 17px; height: 17px; accent-color: var(--green); }

    /* Selling summary */
    .ml-selling { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 16px; padding: 14px 20px; border-radius: 16px; background: var(--mint); font-size: .92rem; color: #35584a; }
    .ml-selling b { color: var(--dark); }
    .ml-selling a { color: var(--green); font-weight: 700; text-decoration: none; }
    .ml-selling a:hover { text-decoration: underline; }

    /* Stats */
    .ml-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 16px; }
    .ml-stat { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 18px 20px; }
    .ml-stat small { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--green); }
    .ml-stat b { display: block; margin: 8px 0 2px; font-size: 1.5rem; font-weight: 800; color: var(--dark); line-height: 1.1; }
    .ml-stat span { font-size: .82rem; color: var(--muted); }

    /* Layout */
    .ml-layout { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr); gap: 16px; align-items: start; }
    .ml-list { display: grid; gap: 14px; }
    .ml-map { padding: 20px; position: sticky; top: 16px; }
    .ml-map h2 { margin: 0 0 2px; font-size: 1.05rem; color: var(--dark); }
    .ml-map > p { margin: 0 0 10px; font-size: .82rem; color: var(--muted); }
    .ml-map { isolation: isolate; }
    #map { height: 400px; border-radius: 14px; overflow: hidden; border: 1px solid var(--line); background: #eef3ef; }
    #map .leaflet-container { font: inherit; }
    .ml-mapfail { margin: 0; padding: 40px 20px; text-align: center; color: var(--muted); font-size: .9rem; }
    .ml-farmpin { width: 22px; height: 22px; border-radius: 50%; background: var(--dark); border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,.35); }
    .leaflet-tooltip.ml-tt { background: transparent; border: 0; box-shadow: none; padding: 0; font-weight: 800; font-size: 11px; color: var(--dark); text-shadow: 0 0 3px #fff, 0 0 3px #fff, 0 0 3px #fff; }
    .leaflet-tooltip.ml-tt::before { display: none; }
    .ml-pop { font-size: .84rem; line-height: 1.5; color: var(--ink); }
    .ml-pop b { color: var(--dark); font-size: .95rem; }
    .ml-pop a { color: var(--green); font-weight: 700; }
    .ml-legend { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 12px; font-size: .78rem; color: var(--muted); }
    .ml-legend em { font-style: normal; display: inline-flex; align-items: center; gap: 6px; }
    .ml-legend em::before { content: ""; width: 9px; height: 9px; border-radius: 50%; background: var(--c, var(--green)); }

    /* Market cards */
    .ml-m { padding: 22px 24px; transition: box-shadow .15s, border-color .15s; scroll-margin-top: 16px; }
    .ml-m.sel { border-color: var(--green); box-shadow: 0 0 0 3px rgba(26,157,92,.22); }
    .ml-m.off { opacity: .6; }
    .ml-m-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    .ml-rank { display: inline-grid; place-items: center; width: 28px; height: 28px; border-radius: 9px; background: var(--mint); color: var(--dark); font-weight: 800; font-size: .84rem; margin-right: 10px; flex: none; }
    .ml-m-name { display: flex; align-items: center; }
    .ml-m-name b { display: block; font-size: 1.08rem; color: var(--dark); }
    .ml-m-name small { color: var(--muted); font-size: .82rem; }
    .ml-star { font: inherit; cursor: pointer; border: 1px solid var(--line); background: #fff; border-radius: 10px; width: 34px; height: 34px; display: grid; place-items: center; flex: none; }
    .ml-star svg { width: 18px; height: 18px; fill: none; stroke: var(--muted); stroke-width: 1.8; stroke-linejoin: round; }
    .ml-star.on svg { fill: var(--amber); stroke: var(--amber); }
    .ml-tags { display: flex; flex-wrap: wrap; gap: 8px; margin: 14px 0 0; }
    .ml-tag { font-size: .74rem; font-weight: 700; padding: 5px 11px; border-radius: 999px; }
    .ml-tag.public { background: #e3f4ea; color: #15753f; }
    .ml-tag.wholesale { background: #fdf2d6; color: #9a6a00; }
    .ml-tag.coop { background: #e2eefa; color: #2a62a0; }
    .ml-tag.open { background: #e3f4ea; color: #15753f; }
    .ml-tag.closed { background: #edf1ef; color: #5b756a; }
    .ml-tag.best { background: var(--dark); color: #fff; }
    .ml-meta { display: grid; grid-template-columns: repeat(3, 1fr); margin-top: 16px; border-top: 1px solid var(--line); }
    .ml-meta div { padding: 14px 12px 2px 0; }
    .ml-meta div + div { padding-left: 14px; border-left: 1px solid var(--line); }
    .ml-meta small { display: block; font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
    .ml-meta b { display: block; margin-top: 5px; font-size: 1rem; color: var(--dark); }
    .ml-meta span { display: block; font-size: .76rem; color: var(--muted); margin-top: 2px; }
    .ml-meta .net b { color: var(--green); font-size: 1.15rem; }
    .ml-nobuy { margin: 14px 0 0; font-size: .88rem; color: var(--muted); }
    .ml-note { margin: 14px 0 0; font-size: .86rem; color: #3b5c4e; line-height: 1.5; }
    .ml-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
    .ml-chip { font-size: .74rem; padding: 4px 10px; border-radius: 8px; background: #f2f7f4; color: var(--muted); font-weight: 600; }
    .ml-chip.on { background: #e3f4ea; color: #15753f; }
    .ml-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
    .ml-btn { font: inherit; font-size: .84rem; font-weight: 700; padding: 9px 16px; border-radius: 10px; border: 1px solid var(--line); background: #fff; color: var(--dark); text-decoration: none; cursor: pointer; transition: all .15s; }
    .ml-btn:hover { border-color: var(--green); color: var(--green); }
    .ml-btn.primary { background: var(--dark); color: #fff; border-color: var(--dark); }
    .ml-btn.primary:hover { background: var(--green); border-color: var(--green); color: #fff; }

    .ml-empty { display: none; padding: 40px; text-align: center; color: var(--muted); background: #fff; border: 1px dashed var(--line); border-radius: 20px; }
    .ml-foot { margin-top: 22px; font-size: .84rem; color: var(--muted); line-height: 1.6; }

    @media (max-width: 1100px) {
      .ml-stats { grid-template-columns: repeat(2, 1fr); }
      .ml-layout { grid-template-columns: 1fr; }
      .ml-map { position: static; order: -1; }
    }
    @media (max-width: 560px) {
      .ml-controls { padding: 16px; }
      .ml-m { padding: 18px; }
      .ml-meta { grid-template-columns: 1fr; }
      .ml-meta div + div { padding-left: 0; border-left: 0; border-top: 1px solid var(--line); margin-top: 10px; }
      .ml-field { min-width: 100%; }
    }
  </style>
</head>
<body>
<div class="app">
  <aside class="side">
    <div class="brand"><span class="brand-mark"><?= ic('leaf') ?></span>AgriTrack</div>
    <nav class="menu" aria-label="Main">
      <?php foreach ($nav as [$href, $label, $icon]): ?>
        <a href="<?= $href ?>" <?= $href === $current ? 'class="active" aria-current="page"' : '' ?>><?= ic($icon) ?><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
    <button class="signout" id="logoutBtn" type="button">Sign out</button>
  </aside>

  <main class="content ml">
    <header class="ml-head">
      <h1>Market locations</h1>
      <p>Find where to sell your harvest and what you would earn after transport.</p>
      <p class="ml-welcome" id="welcome"></p>
      <span class="ml-from">
        <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons['market'] ?></svg>
        <span>Distances from <span id="fromName">--</span></span>
        <a href="weather-forecast.php">Change</a>
      </span>
    </header>

    <section class="ml-card ml-controls">
      <div class="ml-row">
        <div class="ml-field">
          <label for="crop">Crop to sell</label>
          <select id="crop"></select>
        </div>
        <div class="ml-field grow">
          <label for="q">Search markets</label>
          <input type="text" id="q" placeholder="Name or town, e.g. Calauan" autocomplete="off">
        </div>
        <div class="ml-field">
          <label for="sort">Sort by</label>
          <select id="sort">
            <option value="net">Best net revenue</option>
            <option value="price">Highest price</option>
            <option value="near">Nearest first</option>
          </select>
        </div>
      </div>
      <div class="ml-row">
        <div class="ml-field">
          <label>Type</label>
          <div class="ml-pills" id="types" role="group" aria-label="Market type">
            <button type="button" class="ml-pill active" data-type="all">All</button>
            <button type="button" class="ml-pill" data-type="public">Public market</button>
            <button type="button" class="ml-pill" data-type="wholesale">Wholesale</button>
            <button type="button" class="ml-pill" data-type="coop">Cooperative</button>
            <button type="button" class="ml-pill" data-type="saved">Saved</button>
          </div>
        </div>
        <div class="ml-field">
          <label for="km">Within <b id="kmVal">40 km</b></label>
          <input type="range" id="km" min="5" max="40" step="1" value="40">
        </div>
        <div class="ml-field" style="min-width:130px">
          <label for="rate">Transport &#8369; per km</label>
          <input type="number" id="rate" min="0" step="1" value="15">
        </div>
        <label class="ml-check"><input type="checkbox" id="onlyBuying" checked> Only markets that buy this crop</label>
      </div>
    </section>

    <div class="ml-selling" id="selling"></div>

    <section class="ml-stats" id="stats"></section>

    <div class="ml-layout">
      <div>
        <div class="ml-list" id="list"></div>
        <div class="ml-empty" id="empty">No markets match these filters. Try a larger distance or turn off "Only markets that buy this crop".</div>
      </div>

      <aside class="ml-card ml-map">
        <h2>Map</h2>
        <p>Your farm is the dark marker. Tap a market to see details and jump to its card.</p>
        <div id="map"></div>
        <div class="ml-legend">
          <em style="--c:#1a9d5c">Public market</em>
          <em style="--c:#e0a000">Wholesale</em>
          <em style="--c:#3a86c8">Cooperative</em>
        </div>
      </aside>
    </div>

    <p class="ml-foot">
      Net revenue = expected yield &times; the market price, minus a round trip at your transport rate. It is an estimate and ignores loading, tolls and market fees.<br>
      Map data &copy; OpenStreetMap contributors. Market listings, prices and hours are sample data and locations are approximate. Confirm with the market before you deliver.
    </p>
  </main>
</div>

<script type="module" src="../assets/js/auth.js"></script>
<script>
(function () {
  var DATA = <?= $payload ?>;
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
</script>
</body>
</html>