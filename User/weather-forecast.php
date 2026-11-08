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
$current = 'weather-forecast.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriTrack | Weather forecast</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <style>
    /* Weather page only. Every class is prefixed wx- so nothing clashes with dashboard.css */
    .wx {
      --ink: #12382a;
      --dark: #0c4a30;
      --green: #1a9d5c;
      --mint: #eef7f1;
      --line: #e1eee6;
      --muted: #6b8a7a;
      --blue: #3a86c8;
      color: var(--ink);
      font-variant-numeric: tabular-nums;
    }
    .wx * { box-sizing: border-box; }

    /* Header + search */
    .wx-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 26px; }
    .wx-head h1 { margin: 0 0 4px; }
    .wx-head p { margin: 0; color: var(--muted); }
    .wx-head .wx-welcome:empty { display: none; }
    .wx-tools { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
    .wx-search {
      display: flex; align-items: center; width: 340px; max-width: 100%;
      background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 5px 5px 5px 14px;
      transition: border-color .15s, box-shadow .15s;
    }
    .wx-search:focus-within { border-color: var(--green); box-shadow: 0 0 0 4px rgba(26,157,92,.12); }
    .wx-search svg { width: 18px; height: 18px; flex: none; fill: none; stroke: var(--muted); stroke-width: 2; stroke-linecap: round; }
    .wx-search input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; font: inherit; padding: 9px 10px; color: var(--ink); }
    .wx-search button {
      font: inherit; font-weight: 700; font-size: .9rem; cursor: pointer; border: 0; border-radius: 10px;
      padding: 9px 18px; background: var(--dark); color: #fff; transition: background .15s;
    }
    .wx-search button:hover { background: var(--green); }
    .wx-reset { font: inherit; font-size: .83rem; background: none; border: 0; padding: 0; color: var(--muted); cursor: pointer; }
    .wx-reset:hover { color: var(--green); text-decoration: underline; }
    .wx-status { margin: -10px 0 16px; font-size: .9rem; color: var(--muted); }
    .wx-status:empty { display: none; }

    /* Cards */
    .wx-card { background: #fff; border: 1px solid var(--line); border-radius: 20px; transition: opacity .2s; }
    .wx.is-loading .wx-card { opacity: .55; }

    /* Current conditions */
    .wx-now { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr); margin-bottom: 14px; overflow: hidden; }
    .wx-left {
      position: relative; overflow: hidden; padding: 30px 32px; color: #fff;
      background: linear-gradient(145deg, #1fa862 0%, #0f6a42 55%, #0c4a30 100%);
    }
    .wx-left::before, .wx-left::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,.07); pointer-events: none; }
    .wx-left::before { width: 240px; height: 240px; right: -80px; top: -90px; }
    .wx-left::after { width: 150px; height: 150px; right: 70px; bottom: -70px; }
    .wx-left > * { position: relative; z-index: 1; }
    .wx-place { display: flex; align-items: center; gap: 8px; margin: 0; font-weight: 700; font-size: 1.02rem; color: var(--dark); }
    .wx-place svg { width: 18px; height: 18px; flex: none; fill: none; stroke: var(--green); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .wx-date { margin: 4px 0 0 26px; font-size: .86rem; color: var(--muted); }
    .wx-reading { display: flex; align-items: center; gap: 22px; margin: 26px 0 14px; }
    .wx-reading .wx-ic { width: 76px; height: 76px; flex: none; }
    .wx-temp { font-size: 84px; font-weight: 800; line-height: .95; letter-spacing: -3px; color: var(--dark); }
    .wx-temp sup { font-size: 26px; letter-spacing: 0; font-weight: 700; vertical-align: top; position: relative; top: 6px; margin-left: 3px; color: var(--muted); }
    .wx-cond { margin: 0; font-size: 1.15rem; font-weight: 700; }
    .wx-hilo { margin: 4px 0 0; color: var(--muted); font-size: .9rem; }

    /* Light text and icon colours on the green panel */
    .wx-left .wx-place, .wx-left .wx-temp, .wx-left .wx-cond { color: #fff; }
    .wx-left .wx-place svg { stroke: #a5f0c8; }
    .wx-left .wx-date, .wx-left .wx-hilo, .wx-left .wx-temp sup { color: rgba(255,255,255,.78); }
    .wx-left .wx-ic.sun, .wx-left .wx-ic.partly { color: #ffd666; }
    .wx-left .wx-ic.cloud { color: #e3f4ea; }
    .wx-left .wx-ic.fog { color: #d3e9de; }
    .wx-left .wx-ic.rain, .wx-left .wx-ic.snow, .wx-left .wx-ic.storm { color: #c4e4ff; }
    .wx-metric small { color: var(--green); }

    .wx-metrics { display: grid; grid-template-columns: repeat(3, 1fr); }
    .wx-metric { padding: 26px 24px; border-bottom: 1px solid var(--line); border-right: 1px solid var(--line); }
    .wx-metric:nth-child(3n) { border-right: 0; }
    .wx-metric:nth-last-child(-n+3) { border-bottom: 0; }
    .wx-metric small { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
    .wx-metric b { display: block; margin-top: 8px; font-size: 1.5rem; font-weight: 800; color: var(--dark); line-height: 1.1; }
    .wx-metric span { display: block; margin-top: 4px; font-size: .8rem; color: var(--muted); }

    /* Advice */
    .wx-advice {
      --accent: var(--green); --tint: #eaf6ef;
      display: flex; align-items: baseline; flex-wrap: wrap; gap: 6px 14px;
      margin-bottom: 14px; padding: 16px 22px; border-radius: 16px;
      background: var(--tint); border-left: 5px solid var(--accent);
    }
    .wx-advice[data-level="warn"] { --accent: #d99a00; --tint: #fdf5e0; }
    .wx-advice[data-level="alert"] { --accent: #d6453d; --tint: #fcebe9; }
    .wx-advice small { font-size: .7rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--muted); }
    .wx-advice b { font-size: 1rem; color: var(--dark); }
    .wx-advice p { margin: 0; flex-basis: 100%; color: #3b5c4e; line-height: 1.55; }

    /* 7-day forecast */
    .wx-forecast { padding: 22px 28px 12px; }
    .wx-forecast h2 { margin: 0 0 6px; font-size: 1.1rem; color: var(--dark); }
    .wx-row {
      display: grid; align-items: center; gap: 0 16px;
      grid-template-columns: 110px 30px minmax(70px, 1fr) 62px 38px minmax(120px, 1.6fr) 38px;
      padding: 14px 4px; border-top: 1px solid var(--line);
    }
    .wx-row:first-of-type { border-top: 0; }
    .wx-row.today { background: var(--mint); margin: 0 -12px; padding-left: 16px; padding-right: 16px; border-radius: 12px; border-top-color: transparent; }
    .wx-row.today + .wx-row { border-top-color: transparent; }
    .wx-day b { display: block; font-size: .98rem; color: var(--dark); }
    .wx-day small { color: var(--muted); font-size: .78rem; }
    .wx-row .wx-ic { width: 26px; height: 26px; }
    .wx-row-cond { font-size: .88rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .wx-rainpct { display: flex; align-items: center; gap: 5px; font-size: .86rem; font-weight: 700; color: var(--blue); }
    .wx-rainpct svg { width: 13px; height: 13px; fill: currentColor; opacity: .85; }
    .wx-rainpct.dry { color: #a6bcb0; }
    .wx-lo { text-align: right; color: var(--muted); font-weight: 600; }
    .wx-hi { font-weight: 800; color: var(--dark); }
    .wx-range { position: relative; height: 6px; border-radius: 99px; background: #e4efe8; }
    .wx-range i { position: absolute; top: 0; bottom: 0; border-radius: 99px; background: linear-gradient(90deg, #6cc59a, #e6b13a); }

    .wx-foot { margin: 18px 2px 0; font-size: .82rem; color: var(--muted); line-height: 1.7; }
    .wx-foot a { color: var(--green); font-weight: 700; }

    /* Weather icon colours */
    .wx-ic { fill: none; stroke: currentColor; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }
    .wx-ic.sun { color: #e0a000; }
    .wx-ic.partly { color: #d49a1a; }
    .wx-ic.cloud { color: #7f9c8e; }
    .wx-ic.fog { color: #9bb0a5; }
    .wx-ic.rain, .wx-ic.snow, .wx-ic.storm { color: var(--blue); }

    @media (max-width: 1100px) {
      .wx-now { grid-template-columns: 1fr; }
      .wx-left { border-right: 0; border-bottom: 1px solid var(--line); }
    }
    @media (max-width: 760px) {
      .wx-tools { align-items: stretch; width: 100%; }
      .wx-search { width: 100%; }
      .wx-left { padding: 24px 22px; }
      .wx-temp { font-size: 68px; }
      .wx-reading .wx-ic { width: 60px; height: 60px; }
      .wx-metrics { grid-template-columns: repeat(2, 1fr); }
      .wx-metric:nth-child(3n) { border-right: 1px solid var(--line); }
      .wx-metric:nth-child(2n) { border-right: 0; }
      .wx-metric:nth-last-child(-n+3) { border-bottom: 1px solid var(--line); }
      .wx-metric:nth-last-child(-n+2) { border-bottom: 0; }
      .wx-forecast { padding: 18px 16px 8px; }
      .wx-row { grid-template-columns: 78px 28px 52px 34px minmax(60px, 1fr) 34px; }
      .wx-row-cond { display: none; }
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

  <main class="content wx is-loading" id="wxRoot">
    <header class="wx-head">
      <div>
        <h1>Weather forecast</h1>
        <p>Plan your field work with a live local forecast.</p>
        <p class="wx-welcome" id="welcome"></p>
      </div>

      <div class="wx-tools">
        <form class="wx-search" id="searchForm" autocomplete="off">
          <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons['search'] ?></svg>
          <input type="text" id="place" placeholder="Search a town or city" aria-label="Search a town or city" required>
          <button type="submit">Search</button>
        </form>
        <button class="wx-reset" type="button" id="resetLoc">Reset to Bay, Laguna</button>
      </div>
    </header>

    <p class="wx-status" id="status" role="status">Loading forecast...</p>

    <section class="wx-card wx-now">
      <div class="wx-left">
        <p class="wx-place">
          <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons['market'] ?></svg>
          <span id="placeName">--</span>
        </p>
        <p class="wx-date" id="dateLine">--</p>

        <div class="wx-reading">
          <span id="wIcon"></span>
          <div class="wx-temp"><span id="temp">--</span><sup>&deg;C</sup></div>
        </div>
        <p class="wx-cond" id="condition">--</p>
        <p class="wx-hilo" id="hiLo">--</p>
      </div>

      <div class="wx-metrics">
        <div class="wx-metric"><small>Feels like</small><b id="mFeels">--</b><span>Apparent temperature</span></div>
        <div class="wx-metric"><small>Chance of rain</small><b id="mRainChance">--</b><span>Today</span></div>
        <div class="wx-metric"><small>Rainfall</small><b id="mRainToday">--</b><span>Expected today</span></div>
        <div class="wx-metric"><small>Humidity</small><b id="mHumidity">--</b><span>Relative</span></div>
        <div class="wx-metric"><small>Wind</small><b id="mWind">--</b><span>At 10 m</span></div>
        <div class="wx-metric"><small>Daylight</small><b id="mDaylight">--</b><span id="mDaylightSub">Sunrise to sunset</span></div>
      </div>
    </section>

    <section class="wx-advice" id="advice" data-level="good">
      <small>Farm advice</small>
      <b id="tipTitle">Checking conditions...</b>
      <p id="tip">Waiting for the forecast...</p>
    </section>

    <section class="wx-card wx-forecast">
      <h2>7-day forecast</h2>
      <div id="days"></div>
    </section>

    <p class="wx-foot">
      <span id="updated"></span><br>
      Weather data by <a href="https://open-meteo.com/" target="_blank" rel="noopener">Open-Meteo.com</a>.
    </p>
  </main>
</div>
<script type="module" src="../assets/js/auth.js"></script>
<script type="module" src="../assets/js/weather.js"></script>
</body>
</html>