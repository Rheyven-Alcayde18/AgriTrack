<?php
$icons = [
  'home'    => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
  'crop'    => '<path d="M7 20h10"/><path d="M12 20v-9"/><path d="M12 11c0-3 2-5 6-5 0 4-2 6-6 5Z"/><path d="M12 13c0-2.5-1.8-4.5-5.5-4.5 0 3.5 2 5 5.5 4.5Z"/>',
  'weather' => '<path d="M17.5 19a4.5 4.5 0 1 0-1-8.9A6 6 0 0 0 4.7 12 3.5 3.5 0 0 0 6.5 19Z"/>',
  'harvest' => '<path d="M3 9h18l-2 11H5Z"/><path d="M8 9l3-5"/><path d="M16 9l-3-5"/>',
  'market'  => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
  'tips'    => '<path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.7.6 1 1.3 1 2.1h5c0-.8.3-1.5 1-2.1A6 6 0 0 0 12 3Z"/>',
  'leaf'    => '<path d="M6 18c0-8 5-13 13-13 0 8-5 13-13 13Z"/><path d="M6 18 14 10"/>',
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
$current = 'recommendations.php';

// Same records as every other page
require __DIR__ . '/crops-data.php';
require __DIR__ . '/markets-data.php';

$today = new DateTime('today');
$cropRows = [];
foreach ($crops as $c) {
  $cropRows[] = [
    'id' => $c['id'], 'name' => $c['name'], 'field' => $c['field'], 'area' => $c['area'],
    'stage' => $c['stage'], 'progress' => $c['progress'], 'moisture' => $c['moisture'],
    'harvest' => $c['harvest'], 'days' => (int)$today->diff(new DateTime($c['harvest']))->format('%r%a'),
    'yield_t' => $c['yield_t'], 'prep' => $c['prep'],
  ];
}
$payload = json_encode(['crops' => $cropRows, 'markets' => $markets],
  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriTrack | Recommendations</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <style>
    /* Recommendations page only. Every class is prefixed rc- so nothing clashes with dashboard.css */
    .rc {
      --ink: #12382a; --dark: #0c4a30; --green: #1a9d5c; --mint: #eef7f1;
      --line: #e1eee6; --muted: #6b8a7a; --amber: #d99a00; --red: #d6453d; --blue: #3a86c8;
      color: var(--ink); font-variant-numeric: tabular-nums;
    }
    .rc * { box-sizing: border-box; }
    .rc-head { margin-bottom: 20px; }
    .rc-head h1 { margin: 0 0 4px; }
    .rc-head p { margin: 0; color: var(--muted); }
    .rc-head .rc-welcome:empty { display: none; }
    .rc-from { display: inline-flex; align-items: center; gap: 8px; margin-top: 12px; padding: 7px 14px; border-radius: 999px; background: var(--mint); font-size: .84rem; color: var(--dark); font-weight: 600; }
    .rc-from svg { width: 16px; height: 16px; fill: none; stroke: var(--green); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .rc-from a { color: var(--green); font-weight: 700; }
    .rc-card { background: #fff; border: 1px solid var(--line); border-radius: 20px; }

    /* Today's conditions banner */
    .rc-now { position: relative; overflow: hidden; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 18px; padding: 24px 28px; margin-bottom: 16px; color: #fff; border-radius: 20px; background: linear-gradient(135deg, #1fa862 0%, #0f6a42 55%, #0c4a30 100%); }
    .rc-now::before { content: ""; position: absolute; width: 220px; height: 220px; right: -60px; top: -90px; border-radius: 50%; background: rgba(255,255,255,.07); }
    .rc-now > * { position: relative; }
    .rc-now small { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; opacity: .8; }
    .rc-now b { display: block; margin-top: 4px; font-size: 1.5rem; font-weight: 800; line-height: 1.15; }
    .rc-now span { font-size: .84rem; opacity: .85; }
    .rc-now-main { min-width: 220px; }
    .rc-now-stats { display: flex; flex-wrap: wrap; gap: 28px; }
    .rc-verdict { display: inline-block; margin-top: 6px; padding: 5px 12px; border-radius: 999px; font-size: .8rem; font-weight: 800; background: rgba(255,255,255,.18); }
    .rc-now.loading b { opacity: .6; }

    /* Stats */
    .rc-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 16px; }
    .rc-stat { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 18px 20px; }
    .rc-stat small { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--green); }
    .rc-stat b { display: block; margin: 8px 0 2px; font-size: 1.6rem; font-weight: 800; color: var(--dark); line-height: 1.1; }
    .rc-stat.urgent b { color: var(--red); }
    .rc-stat span { font-size: .82rem; color: var(--muted); }

    /* Planning window */
    .rc-plan { padding: 20px 24px; margin-bottom: 16px; }
    .rc-plan h2 { margin: 0 0 2px; font-size: 1.05rem; color: var(--dark); }
    .rc-plan > p { margin: 0 0 14px; font-size: .84rem; color: var(--muted); }
    .rc-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 10px; }
    .rc-day { border: 1px solid var(--line); border-radius: 14px; padding: 12px 8px; text-align: center; background: #fbfdfc; }
    .rc-day.today { border-color: var(--green); background: var(--mint); }
    .rc-day b { display: block; font-size: .86rem; color: var(--dark); }
    .rc-day small { display: block; margin: 4px 0 8px; font-size: .74rem; color: var(--muted); line-height: 1.5; }
    .rc-rate { display: inline-block; font-size: .7rem; font-weight: 800; padding: 3px 9px; border-radius: 999px; }
    .rc-rate.good { background: #e3f4ea; color: #15753f; }
    .rc-rate.caution { background: #fdf2d6; color: #9a6a00; }
    .rc-rate.avoid { background: #fde6e3; color: #b13a2f; }

    /* Filters */
    .rc-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .rc-bar h2 { margin: 0; font-size: 1.15rem; color: var(--dark); }
    .rc-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .rc-pill { font: inherit; font-size: .84rem; font-weight: 700; cursor: pointer; padding: 8px 15px; border-radius: 999px; background: #fff; color: var(--muted); border: 1px solid var(--line); transition: all .15s; }
    .rc-pill:hover { color: var(--dark); border-color: var(--green); }
    .rc-pill.active { background: var(--dark); color: #fff; border-color: var(--dark); }
    .rc-pill i { font-style: normal; margin-left: 6px; opacity: .7; }
    .rc-check { display: flex; align-items: center; gap: 8px; font-size: .86rem; cursor: pointer; margin-bottom: 14px; color: var(--ink); }
    .rc-check input { width: 17px; height: 17px; accent-color: var(--green); }

    /* Recommendation cards */
    .rc-list { display: grid; gap: 14px; }
    .rc-r { --accent: var(--green); padding: 22px 26px; border-left: 6px solid var(--accent); transition: opacity .2s; }
    .rc-r.p0 { --accent: var(--red); }
    .rc-r.p1 { --accent: var(--amber); }
    .rc-r.done { opacity: .55; }
    .rc-r.done h3 { text-decoration: line-through; }
    .rc-r-top { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px 12px; }
    .rc-tags { display: flex; flex-wrap: wrap; gap: 8px; }
    .rc-tag { font-size: .72rem; font-weight: 800; padding: 4px 11px; border-radius: 999px; background: #eef2f0; color: #4d6b5d; }
    .rc-tag.pri0 { background: #fde6e3; color: #b13a2f; }
    .rc-tag.pri1 { background: #fdf2d6; color: #9a6a00; }
    .rc-tag.pri2 { background: #e3f4ea; color: #15753f; }
    .rc-tag.crop { background: var(--mint); color: var(--dark); }
    .rc-due { font-size: .8rem; color: var(--muted); font-weight: 600; }
    .rc-r h3 { margin: 12px 0 6px; font-size: 1.1rem; color: var(--dark); }
    .rc-why { margin: 0 0 12px; font-size: .92rem; color: #3b5c4e; line-height: 1.55; }
    .rc-steps { margin: 0; padding: 14px 18px 14px 34px; border-radius: 14px; background: var(--mint); font-size: .9rem; line-height: 1.7; color: #35584a; }
    .rc-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
    .rc-btn { font: inherit; font-size: .84rem; font-weight: 700; padding: 9px 16px; border-radius: 10px; border: 1px solid var(--line); background: #fff; color: var(--dark); text-decoration: none; cursor: pointer; transition: all .15s; }
    .rc-btn:hover { border-color: var(--green); color: var(--green); }
    .rc-btn.primary { background: var(--dark); color: #fff; border-color: var(--dark); }
    .rc-btn.primary:hover { background: var(--green); border-color: var(--green); color: #fff; }
    .rc-empty { display: none; padding: 40px; text-align: center; color: var(--muted); background: #fff; border: 1px dashed var(--line); border-radius: 20px; }
    .rc-notice { display: none; margin-bottom: 14px; padding: 12px 18px; border-radius: 14px; background: #fdf6e0; color: #7a5a00; font-size: .88rem; }
    .rc-foot { margin-top: 22px; font-size: .84rem; color: var(--muted); line-height: 1.6; }

    @media (max-width: 1100px) {
      .rc-stats { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 760px) {
      .rc-days { display: flex; overflow-x: auto; padding-bottom: 6px; }
      .rc-day { flex: 0 0 112px; }
      .rc-now { padding: 20px; }
      .rc-r { padding: 18px; }
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

  <main class="content rc">
    <header class="rc-head">
      <h1>Recommendations</h1>
      <p>Daily actions based on your crops, the weather forecast and nearby markets.</p>
      <p class="rc-welcome" id="welcome"></p>
      <span class="rc-from">
        <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons['market'] ?></svg>
        <span>Forecast for <span id="fromName">--</span></span>
        <a href="weather-forecast.php">Change</a>
      </span>
    </header>

    <section class="rc-now loading" id="now">
      <div class="rc-now-main">
        <small>Right now</small>
        <b id="nowTemp">--</b>
        <span id="nowCond">Loading forecast...</span>
      </div>
      <div class="rc-now-stats">
        <div><small>Rain today</small><b id="nowRain">--</b></div>
        <div><small>Wind</small><b id="nowWind">--</b></div>
        <div><small>Field work today</small><b id="nowVerdict">--</b></div>
      </div>
    </section>

    <div class="rc-notice" id="notice">The weather forecast could not be loaded, so only crop and market recommendations are shown.</div>

    <section class="rc-stats" id="stats"></section>

    <section class="rc-card rc-plan" id="planCard">
      <h2>This week's planning window</h2>
      <p>Good days are dry and calm, which suits spraying, fertilizing and harvesting.</p>
      <div class="rc-days" id="days"></div>
    </section>

    <div class="rc-bar">
      <h2>Your action list</h2>
      <div class="rc-pills" id="cats" role="group" aria-label="Filter recommendations"></div>
    </div>
    <label class="rc-check"><input type="checkbox" id="showDone"> Show completed for today</label>

    <div class="rc-list" id="list"></div>
    <div class="rc-empty" id="empty">Nothing to do here. Nice work.</div>

    <p class="rc-foot">
      Recommendations are generated from your crop records, today's Open-Meteo forecast and the market listings. They are general guidance, so adjust them to your own field conditions.
      Completed items reset each day. Crop, market and price figures are sample data until your farm records are connected.
    </p>
  </main>
</div>

<script type="module" src="../assets/js/auth.js"></script>
<script>
(function () {
  var DATA = <?= $payload ?>;
  var DEFAULT_FARM = { name: 'Bay, Laguna, Philippines', lat: 14.1833, lon: 121.2833 };
  var DONE_KEY = 'agritrack.recsDone';
  var RATE = 15; // transport cost per km used to compare markets
  var CATS = { water: 'Watering', weather: 'Weather', harvest: 'Harvest', market: 'Selling', care: 'Crop care' };
  var PRI = ['Urgent', 'This week', 'Plan ahead'];
  var DAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  var $ = function (id) { return document.getElementById(id); };
  var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var peso = function (n) { return '₱' + Math.round(n).toLocaleString('en-US'); };

  function getFarm() {
    try {
      var v = JSON.parse(localStorage.getItem('agritrack.location'));
      if (v && isFinite(v.lat) && isFinite(v.lon) && v.name) return v;
    } catch (e) {}
    return DEFAULT_FARM;
  }
  var farm = getFarm();
  $('fromName').textContent = farm.name;

  function todayKey() {
    return new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Manila' }).format(new Date());
  }
  var TODAY = todayKey();
  var done = {};
  try {
    var stored = JSON.parse(localStorage.getItem(DONE_KEY)) || {};
    Object.keys(stored).forEach(function (k) { if (k.split('|')[1] === TODAY) done[k] = true; }); // old days reset
    localStorage.setItem(DONE_KEY, JSON.stringify(done));
  } catch (e) {}
  function saveDone() { try { localStorage.setItem(DONE_KEY, JSON.stringify(done)); } catch (e) {} }
  var isDone = function (r) { return !!done[r.id + '|' + TODAY]; };

  function kmBetween(lat1, lon1, lat2, lon2) {
    var R = 6371, rad = Math.PI / 180, dLat = (lat2 - lat1) * rad, dLon = (lon2 - lon1) * rad;
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }
  function clock(t) {
    var p = t.split(':'), h = parseInt(p[0], 10);
    return ((h + 11) % 12 + 1) + (p[1] !== '00' ? ':' + p[1] : '') + ' ' + (h >= 12 ? 'PM' : 'AM');
  }
  function describe(code) {
    if (code === 0) return 'Clear sky';
    if (code <= 2) return 'Partly cloudy';
    if (code === 3) return 'Overcast';
    if (code <= 48) return 'Foggy';
    if (code <= 57) return 'Drizzle';
    if (code <= 67) return 'Rain';
    if (code <= 77) return 'Snow';
    if (code <= 82) return 'Rain showers';
    if (code <= 86) return 'Snow showers';
    return 'Thunderstorm';
  }
  function dueText(days) {
    if (days > 0) return 'Harvest in ' + days + ' day' + (days === 1 ? '' : 's');
    return days === 0 ? 'Harvest today' : 'Ready to harvest';
  }

  /* ---------- Weather helpers (null when the forecast is unavailable) ---------- */
  var wx = null;
  function dayInfo(i) {
    var d = wx.daily, rain = d.precipitation_probability_max[i] || 0, wind = d.wind_speed_10m_max[i] || 0, code = d.weather_code[i];
    var rate = (rain >= 60 || code >= 95) ? 'avoid' : (rain >= 30 || wind >= 25 || d.temperature_2m_max[i] >= 35) ? 'caution' : 'good';
    var date = new Date(d.time[i] + 'T00:00:00');
    return { rain: rain, wind: Math.round(wind), max: Math.round(d.temperature_2m_max[i]), code: code, rate: rate,
             name: i === 0 ? 'Today' : DAY[date.getDay()], full: i === 0 ? 'today' : date.toLocaleDateString('en-US', { weekday: 'long' }) };
  }

  /* ---------- Build the recommendations ---------- */
  function build() {
    var recs = [];
    function add(r) { recs.push(r); }
    var today = wx ? dayInfo(0) : null;
    var tomorrow = wx ? dayInfo(1) : null;

    DATA.crops.forEach(function (c) {
      var link = function (page, label) { return { href: page + '#crop-' + c.id, label: label }; };

      // Watering
      if (c.moisture < 35) {
        if (today && today.rain >= 70) {
          add({ id: 'water-' + c.id, pri: 1, cat: 'water', crop: c.name, due: 'Today',
            title: c.name + ' soil is dry, but rain is likely',
            why: 'Soil moisture in ' + c.field + ' is ' + c.moisture + '%, below the healthy 35%. Rain chance today is ' + today.rain + '%.',
            steps: ['Check the field after the rain', 'Water this evening if the soil is still dry', 'Check moisture again tomorrow morning'],
            link: link('crop-monitoring.php', 'View crop') });
        } else {
          var steps = ['Irrigate in the early morning or late afternoon', 'Water slowly so it soaks in instead of running off', 'Check moisture again tomorrow'];
          if (today && today.max >= 33) steps.push('Today reaches ' + today.max + '°C, so water a little extra');
          add({ id: 'water-' + c.id, pri: 0, cat: 'water', crop: c.name, due: 'Today',
            title: 'Water ' + c.name + ' today',
            why: 'Soil moisture in ' + c.field + ' is ' + c.moisture + '%, below the healthy range of 35% or more.' + (c.stage === 'Seedling' ? ' Young seedlings dry out fastest.' : ''),
            steps: steps, link: link('crop-monitoring.php', 'View crop') });
        }
      } else if (c.moisture < 50 && c.id !== 'rice') {
        add({ id: 'watersoon-' + c.id, pri: 2, cat: 'water', crop: c.name, due: 'Next 2 days',
          title: 'Plan watering for ' + c.name,
          why: 'Soil moisture in ' + c.field + ' is ' + c.moisture + '% and drifting toward the dry range.',
          steps: ['Water within the next two days unless rain arrives', 'Mulch around plants to hold moisture'],
          link: link('crop-monitoring.php', 'View crop') });
      } else if (c.moisture > 78 && c.id !== 'rice' && wx) {
        var wet = 0; for (var k = 0; k < 3; k++) wet = Math.max(wet, dayInfo(k).rain);
        if (wet >= 60) {
          add({ id: 'drain-' + c.id, pri: 1, cat: 'water', crop: c.name, due: 'Before the rain',
            title: 'Check drainage in ' + c.field,
            why: 'The soil is already at ' + c.moisture + '% moisture and rain chance reaches ' + wet + '% in the next three days.',
            steps: ['Clear drainage channels', 'Hold off irrigation', 'Watch for standing water after rain'],
            link: link('crop-monitoring.php', 'View crop') });
        }
      }

      // Harvest preparation
      if (c.days <= 14) {
        var hp = c.days <= 3 ? 0 : (c.days <= 7 ? 1 : 2);
        var title = c.days <= 0 ? 'Harvest ' + c.name + ' now' : 'Prepare to harvest ' + c.name;
        add({ id: 'harvest-' + c.id, pri: hp, cat: 'harvest', crop: c.name, due: dueText(c.days),
          title: title,
          why: c.name + ' in ' + c.field + ' is ' + c.progress + '% grown and due on ' + c.harvest + '. Expected yield is ' + c.yield_t + ' t.',
          steps: c.prep.concat(['Check the weather the day before picking']),
          link: link('harvest-management.php', 'Open harvest plan') });

        // Rain before harvest
        if (wx && c.days <= 7) {
          var from = Math.max(c.days - 1, 0), limit = Math.min(Math.max(c.days, 0), 6), rainy = -1;
          for (var i = from; i <= limit; i++) { if (dayInfo(i).rain >= 60) { rainy = i; break; } }
          if (rainy > -1) {
            var di = dayInfo(rainy);
            add({ id: 'rainharvest-' + c.id, pri: 1, cat: 'weather', crop: c.name, due: 'Around ' + di.full,
              title: 'Rain is forecast near the ' + c.name.toLowerCase() + ' harvest',
              why: 'Rain chance reaches ' + di.rain + '% ' + (rainy === 0 ? 'today' : 'on ' + di.full) + ', and ' + c.name.toLowerCase() + ' is due ' + c.harvest + '. Wet weather can delay picking and spoil produce.',
              steps: ['Harvest on a dry day when the crop is ready', 'Move picking earlier if the crop is ready and you can', 'Keep covers and dry storage ready'],
              link: link('harvest-management.php', 'Open harvest plan') });
          }
        }
      }

      // Where to sell
      if (c.days <= 30 && c.days >= -5) {
        var rows = DATA.markets.filter(function (m) { return m.prices[c.id] != null; }).map(function (m) {
          var dist = kmBetween(farm.lat, farm.lon, m.lat, m.lon);
          return { m: m, dist: dist, price: m.prices[c.id], net: c.yield_t * 1000 * m.prices[c.id] - dist * 2 * RATE };
        }).sort(function (a, b) { return b.net - a.net; });
        if (rows.length) {
          var b1 = rows[0], b2 = rows[1];
          var st = ['Call ahead to confirm today’s price', 'Open ' + clock(b1.m.from) + ' to ' + clock(b1.m.to) + '. ' + b1.m.note];
          if (b2) st.push('Next best: ' + b2.m.name + ' at ' + peso(b2.net) + ' (' + b2.dist.toFixed(1) + ' km)');
          add({ id: 'sell-' + c.id, pri: c.days <= 7 ? 1 : 2, cat: 'market', crop: c.name, due: dueText(c.days),
            title: 'Sell ' + c.name + ' at ' + b1.m.name,
            why: 'Best estimated earnings: ' + peso(b1.net) + ' for ' + c.yield_t + ' t at ' + peso(b1.price) + '/kg, ' + b1.dist.toFixed(1) + ' km away, after round-trip transport.',
            steps: st, link: { href: 'market-locations.php?crop=' + c.id, label: 'Compare markets' } });
        }
      }

      // Crop care by growth stage
      var care = null;
      if (c.stage === 'Seedling') {
        care = { t: 'Protect ' + c.name + ' seedlings', s: ['Check for pests and weeds twice a week', 'Keep soil moist but not soaked', 'Replace failed seedlings early'] };
      } else if (c.stage === 'Vegetative') {
        var rainSoon = wx && (today.rain >= 60 || tomorrow.rain >= 60);
        care = { t: 'Feed ' + c.name + ' with fertilizer',
          s: [rainSoon ? 'Rain is likely soon, so wait for a dry day to avoid washing it away' : 'Apply when no heavy rain is expected for 24 hours',
              'Water lightly afterwards so it reaches the roots', 'Keep fertilizer off the leaves'] };
      } else if (c.stage === 'Flowering') {
        care = { t: 'Keep ' + c.name + ' water steady', s: ['Avoid water stress during flowering', 'Scout for pests', 'Avoid spraying in the midday heat'] };
      } else if (c.stage === 'Fruiting') {
        care = c.id === 'rice'
          ? { t: 'Look after ' + c.name + ' during grain filling', s: ['Keep the field moist until grain filling finishes', 'Plan to drain the field about a week before harvest', 'Watch for rice bugs and birds'] }
          : { t: 'Watch ' + c.name + ' for pests and support the fruit', s: ['Check for fruit borers and disease', 'Support heavy plants with stakes', 'Water at the base, not over the leaves'] };
      }
      if (care) {
        add({ id: 'care-' + c.id, pri: 2, cat: 'care', crop: c.name, due: 'This week',
          title: care.t,
          why: c.name + ' in ' + c.field + ' is at the ' + c.stage.toLowerCase() + ' stage (' + c.progress + '% grown).',
          steps: care.s, link: link('crop-monitoring.php', 'View crop') });
      }
    });

    // Weather for the whole farm
    if (wx) {
      var stormIdx = -1;
      for (var s = 0; s < 3; s++) { if (dayInfo(s).code >= 95) { stormIdx = s; break; } }
      if (stormIdx > -1) {
        var sd = dayInfo(stormIdx);
        add({ id: 'storm', pri: 0, cat: 'weather', crop: null, due: sd.name,
          title: 'Thunderstorms expected ' + (stormIdx === 0 ? 'today' : sd.full),
          why: 'The forecast shows thunderstorms. Open fields are unsafe during lightning.',
          steps: ['Stay out of open fields during the storm', 'Secure equipment, covers and stakes', 'Harvest anything ready beforehand'],
          link: { href: 'weather-forecast.php', label: 'See forecast' } });
      }
      if (today.max >= 34) {
        add({ id: 'heat', pri: 1, cat: 'weather', crop: null, due: 'Today',
          title: 'Hot day ahead',
          why: 'Today’s high is ' + today.max + '°C, which stresses plants and workers.',
          steps: ['Water early morning or late afternoon', 'Give workers shade and water breaks', 'Avoid spraying and fertilizing at midday'],
          link: { href: 'weather-forecast.php', label: 'See forecast' } });
      }
      var windy = -1;
      for (var w = 0; w < 3; w++) { if (dayInfo(w).wind >= 30) { windy = w; break; } }
      if (windy > -1) {
        add({ id: 'wind', pri: 1, cat: 'weather', crop: null, due: dayInfo(windy).name,
          title: 'Strong winds forecast',
          why: 'Wind could reach ' + dayInfo(windy).wind + ' km/h ' + (windy === 0 ? 'today' : 'on ' + dayInfo(windy).full) + '.',
          steps: ['Secure covers, stakes and loose equipment', 'Do not spray, since the wind will carry it away', 'Check tall plants for damage afterwards'],
          link: { href: 'weather-forecast.php', label: 'See forecast' } });
      }
      var spray = -1;
      for (var p = 0; p < 7; p++) { var q = dayInfo(p); if (q.rain < 30 && q.wind < 20) { spray = p; break; } }
      if (spray > -1) {
        var sp = dayInfo(spray);
        add({ id: 'spray', pri: 2, cat: 'weather', crop: null, due: sp.name,
          title: 'Best day to spray: ' + sp.full,
          why: 'Rain chance is ' + sp.rain + '% and wind is ' + sp.wind + ' km/h, so spray should stay on the plants.',
          steps: ['Spray in the early morning', 'Skip crops that are due for harvest soon', 'Avoid the hottest hours'],
          link: { href: 'weather-forecast.php', label: 'See forecast' } });
      } else {
        add({ id: 'nospray', pri: 2, cat: 'weather', crop: null, due: 'This week',
          title: 'No good spraying day this week',
          why: 'Every day in the forecast has rain or strong wind. Spraying now would likely wash away or drift.',
          steps: ['Delay non-urgent spraying', 'Check the forecast again tomorrow'],
          link: { href: 'weather-forecast.php', label: 'See forecast' } });
      }
    }

    var order = { water: 0, harvest: 1, weather: 2, market: 3, care: 4 };
    recs.sort(function (a, b) { return a.pri - b.pri || order[a.cat] - order[b.cat]; });
    return recs;
  }

  /* ---------- Rendering ---------- */
  var state = { cat: 'all', showDone: false };
  var recs = [];

  function renderBanner() {
    var box = $('now');
    box.classList.remove('loading');
    if (!wx) { $('nowTemp').textContent = '--'; $('nowCond').textContent = 'Forecast unavailable'; return; }
    var c = wx.current, t = dayInfo(0);
    $('nowTemp').textContent = Math.round(c.temperature_2m) + '°C';
    $('nowCond').textContent = describe(c.weather_code) + ' in ' + farm.name.split(',')[0];
    $('nowRain').textContent = t.rain + '%';
    $('nowWind').textContent = Math.round(c.wind_speed_10m) + ' km/h';
    var v = { good: 'Good', caution: 'Use caution', avoid: 'Avoid' }[t.rate];
    $('nowVerdict').textContent = v;
  }

  function renderPlan() {
    var card = $('planCard');
    if (!wx) { card.style.display = 'none'; return; }
    var html = '';
    for (var i = 0; i < 7; i++) {
      var d = dayInfo(i), label = { good: 'Good', caution: 'Caution', avoid: 'Avoid' }[d.rate];
      html += '<div class="rc-day' + (i === 0 ? ' today' : '') + '"><b>' + esc(d.name) + '</b>' +
        '<small>' + d.rain + '% rain<br>' + d.wind + ' km/h wind<br>' + d.max + '°C</small>' +
        '<span class="rc-rate ' + d.rate + '">' + label + '</span></div>';
    }
    $('days').innerHTML = html;
  }

  function renderStats() {
    var open = recs.filter(function (r) { return !isDone(r); });
    var urgent = open.filter(function (r) { return r.pri === 0; }).length;
    var week = open.filter(function (r) { return r.pri <= 1; }).length;
    var doneCount = recs.length - open.length;
    var soon = DATA.crops.slice().sort(function (a, b) { return a.days - b.days; })[0];
    var cells = [
      ['Urgent', String(urgent), urgent ? 'Do these first' : 'Nothing urgent', urgent ? ' urgent' : ''],
      ['Needs action this week', String(week), 'Urgent and this-week items', ''],
      ['Done today', doneCount + ' of ' + recs.length, 'Resets each day', ''],
      ['Next harvest', soon.name, dueText(soon.days), '']
    ];
    $('stats').innerHTML = cells.map(function (c) {
      return '<div class="rc-stat' + c[3] + '"><small>' + esc(c[0]) + '</small><b>' + esc(c[1]) + '</b><span>' + esc(c[2]) + '</span></div>';
    }).join('');
  }

  function renderCats() {
    var counts = { all: 0 };
    recs.forEach(function (r) { if (!isDone(r) || state.showDone) { counts.all++; counts[r.cat] = (counts[r.cat] || 0) + 1; } });
    var html = '<button type="button" class="rc-pill' + (state.cat === 'all' ? ' active' : '') + '" data-cat="all">All<i>' + counts.all + '</i></button>';
    Object.keys(CATS).forEach(function (k) {
      if (!counts[k]) return;
      html += '<button type="button" class="rc-pill' + (state.cat === k ? ' active' : '') + '" data-cat="' + k + '">' + CATS[k] + '<i>' + counts[k] + '</i></button>';
    });
    $('cats').innerHTML = html;
  }

  function renderList() {
    var list = recs.filter(function (r) {
      if (state.cat !== 'all' && r.cat !== state.cat) return false;
      return state.showDone || !isDone(r);
    });
    $('empty').style.display = list.length ? 'none' : 'block';
    $('list').innerHTML = list.map(function (r) {
      var dn = isDone(r);
      return '<article class="rc-card rc-r p' + r.pri + (dn ? ' done' : '') + '">' +
        '<div class="rc-r-top"><div class="rc-tags">' +
          '<span class="rc-tag pri' + r.pri + '">' + PRI[r.pri] + '</span>' +
          '<span class="rc-tag">' + esc(CATS[r.cat]) + '</span>' +
          (r.crop ? '<span class="rc-tag crop">' + esc(r.crop) + '</span>' : '') +
        '</div><span class="rc-due">' + esc(r.due) + '</span></div>' +
        '<h3>' + esc(r.title) + '</h3>' +
        '<p class="rc-why">' + esc(r.why) + '</p>' +
        '<ul class="rc-steps">' + r.steps.map(function (s) { return '<li>' + esc(s) + '</li>'; }).join('') + '</ul>' +
        '<div class="rc-actions">' +
          '<button type="button" class="rc-btn primary" data-done="' + esc(r.id) + '">' + (dn ? 'Undo' : 'Mark as done') + '</button>' +
          '<a class="rc-btn" href="' + esc(r.link.href) + '">' + esc(r.link.label) + ' &rarr;</a>' +
        '</div></article>';
    }).join('');
  }

  function render() { renderStats(); renderCats(); renderList(); }

  $('cats').addEventListener('click', function (e) {
    var b = e.target.closest('.rc-pill'); if (!b) return;
    state.cat = b.getAttribute('data-cat'); render();
  });
  $('showDone').addEventListener('change', function () { state.showDone = this.checked; render(); });
  $('list').addEventListener('click', function (e) {
    var b = e.target.closest('[data-done]'); if (!b) return;
    var key = b.getAttribute('data-done') + '|' + TODAY;
    if (done[key]) delete done[key]; else done[key] = true;
    saveDone(); render();
  });

  function start() {
    renderBanner(); renderPlan();
    recs = build();
    if (state.cat !== 'all' && !recs.some(function (r) { return r.cat === state.cat; })) state.cat = 'all';
    render();
  }

  // Crop and market recommendations show straight away, weather ones join when the forecast arrives
  recs = build(); render();

  var url = 'https://api.open-meteo.com/v1/forecast?latitude=' + farm.lat + '&longitude=' + farm.lon +
    '&current=temperature_2m,weather_code,wind_speed_10m,relative_humidity_2m' +
    '&daily=weather_code,temperature_2m_max,precipitation_probability_max,wind_speed_10m_max&timezone=auto&forecast_days=7';
  fetch(url)
    .then(function (r) { if (!r.ok) throw new Error('bad response'); return r.json(); })
    .then(function (d) { wx = d; start(); })
    .catch(function () { $('notice').style.display = 'block'; wx = null; start(); });
})();
</script>
</body>
</html>