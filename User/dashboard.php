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
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function num($n) { return rtrim(rtrim(number_format($n, 1), '0'), '.'); }

$nav = [
  ['dashboard.php', 'Dashboard', 'home'],
  ['crop-monitoring.php', 'Crop monitoring', 'crop'],
  ['weather-forecast.php', 'Weather forecast', 'weather'],
  ['harvest-management.php', 'Harvest management', 'harvest'],
  ['market-locations.php', 'Market locations', 'market'],
  ['recommendations.php', 'Recommendations', 'tips'],
];
$current = 'dashboard.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriTrack | Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="../assets/css/agri-ui.css">
  <style>
    /* Dashboard page only. Every class is prefixed db- so nothing clashes with dashboard.css */
    .db {
      --ink: #12382a; --dark: #0c4a30; --green: #1a9d5c; --mint: #eef7f1;
      --line: #e1eee6; --muted: #6b8a7a; --amber: #d99a00; --red: #d6453d;
      color: var(--ink); font-variant-numeric: tabular-nums;
    }
    .db * { box-sizing: border-box; }
    .db-head { margin-bottom: 22px; }
    .db-head h1 { margin: 0 0 4px; }
    .db-head p { margin: 0; color: var(--muted); }
    .db-card { background: #fff; border: 1px solid var(--line); border-radius: 20px; padding: 22px 24px; }
    .db-card-head { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
    .db-card h2 { margin: 0; font-size: 1.1rem; color: var(--dark); }
    .db-link { font-size: .84rem; font-weight: 700; color: var(--green); text-decoration: none; white-space: nowrap; }
    .db-link:hover { text-decoration: underline; }

    /* Attention banner */
    .db-alert { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px 16px; margin-bottom: 16px; padding: 14px 20px; border-radius: 16px; background: #fdf0ee; border-left: 5px solid var(--red); font-size: .92rem; color: #7a2c26; }
    .db-alert b { color: #8f261f; }

    /* Stats */
    .db-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 16px; }
    .db-stat { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 18px 20px; }
    .db-stat small { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--green); }
    .db-stat b { display: block; margin: 8px 0 2px; font-size: 1.7rem; font-weight: 800; color: var(--dark); line-height: 1.1; }
    .db-stat span { font-size: .82rem; color: var(--muted); }

    /* Layout */
    .db-cols { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 16px; align-items: start; }
    .db-stack { display: grid; gap: 16px; }

    /* Crop progress */
    .db-crop { display: grid; grid-template-columns: 150px minmax(0, 1fr) auto; gap: 18px; align-items: center; padding: 16px 0; border-top: 1px solid var(--line); }
    .db-crop:first-of-type { border-top: 0; }
    .db-crop b { display: block; color: var(--dark); font-size: 1rem; }
    .db-crop small { color: var(--muted); font-size: .8rem; }
    .db-bar { display: flex; align-items: center; gap: 12px; }
    .db-bar .tr { flex: 1; height: 8px; border-radius: 99px; background: #e4efe8; overflow: hidden; }
    .db-bar .tr i { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, #4cc088, #1a9d5c); }
    .db-bar span { min-width: 38px; text-align: right; font-size: .86rem; font-weight: 700; color: var(--dark); }
    .db-pill { font-size: .76rem; font-weight: 700; padding: 5px 12px; border-radius: 999px; white-space: nowrap; }
    .db-pill.ok { background: #e3f4ea; color: #15753f; }
    .db-pill.soon { background: #fdf2d6; color: #9a6a00; }
    .db-pill.warn { background: #fde6e3; color: #b13a2f; }

    /* Yield chart */
    .db-chart { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; align-items: end; height: 210px; padding-top: 10px; }
    .db-col { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 6px; }
    .db-col em { font-style: normal; font-size: .8rem; font-weight: 700; color: var(--muted); }
    .db-col .bar { width: 100%; max-width: 54px; flex: none; border-radius: 10px 10px 4px 4px; background: #cfe9da; }
    .db-col.now .bar { background: linear-gradient(180deg, #2bb56f, #0f6a42); }
    .db-col.now em { color: var(--dark); }
    .db-col small { font-size: .78rem; color: var(--muted); font-weight: 600; }

    /* Weather */
    .db-wx { position: relative; overflow: hidden; color: #fff; border: 0; background: linear-gradient(145deg, #1fa862 0%, #0f6a42 55%, #0c4a30 100%); }
    .db-wx::before { content: ""; position: absolute; width: 200px; height: 200px; right: -60px; top: -80px; border-radius: 50%; background: rgba(255,255,255,.07); }
    .db-wx > * { position: relative; }
    .db-wx h2 { color: #fff; }
    .db-wx .db-link { color: #b9f3d3; }
    .db-wx-place { margin: 0; font-size: .86rem; opacity: .85; }
    .db-wx-temp { margin: 10px 0 0; font-size: 3.4rem; font-weight: 800; letter-spacing: -2px; line-height: 1; }
    .db-wx-cond { margin: 6px 0 0; font-weight: 700; }
    .db-wx-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 18px; }
    .db-wx-stats div { background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18); border-radius: 14px; padding: 10px 12px; }
    .db-wx-stats small { display: block; font-size: .68rem; opacity: .8; margin-top: 3px; }
    .db-wx-stats b { font-size: 1rem; font-weight: 800; }

    /* Up next */
    .db-next { display: grid; grid-template-columns: 54px minmax(0, 1fr) auto; gap: 14px; align-items: center; padding: 14px 0; border-top: 1px solid var(--line); }
    .db-next:first-of-type { border-top: 0; }
    .db-date { text-align: center; border-radius: 14px; background: var(--mint); padding: 8px 0; }
    .db-date small { display: block; font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--green); }
    .db-date b { font-size: 1.3rem; color: var(--dark); line-height: 1.1; }
    .db-next-info b { display: block; color: var(--dark); }
    .db-next-info small { color: var(--muted); font-size: .8rem; }
    .db-next-days { font-size: .8rem; font-weight: 700; color: var(--green); text-align: right; white-space: nowrap; }

    /* To do */
    .db-tasks { list-style: none; margin: 4px 0 0; padding: 0; }
    .db-tasks li { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid var(--line); }
    .db-tasks li:first-child { border-top: 0; }
    .db-tasks input { width: 18px; height: 18px; accent-color: var(--green); flex: none; cursor: pointer; }
    .db-tasks label { flex: 1; cursor: pointer; font-size: .92rem; }
    .db-tasks input:checked + label { color: var(--muted); text-decoration: line-through; }
    .db-tasks small { color: var(--muted); font-size: .78rem; font-weight: 600; white-space: nowrap; }

    .db-note { margin-top: 22px; font-size: .84rem; color: var(--muted); }

    @media (max-width: 1100px) {
      .db-stats { grid-template-columns: repeat(2, 1fr); }
      .db-cols { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
      .db-card { padding: 18px; }
      .db-crop { grid-template-columns: minmax(0, 1fr) auto; gap: 10px 12px; }
      .db-crop .db-bar { grid-column: 1 / -1; order: 3; }
      .db-chart { gap: 8px; }
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

  <main class="content db">
    <header class="db-head">
      <h1 id="welcome">Loading...</h1>
      <p><?= date('l, F j') ?>. Here is how your farm is doing today.</p>
    </header>

    <div id="alertBox"></div>

    <section class="db-stats" id="stats" aria-live="polite"></section>

    <div class="db-cols">
      <div class="db-stack">
        <section class="db-card">
          <div class="db-card-head">
            <h2>Crop progress</h2>
            <a class="db-link" href="crop-monitoring.php">View all &rarr;</a>
          </div>
          <div id="cropList"><div class="ag-loading">Loading&hellip;</div></div>
        </section>

        <section class="db-card">
          <div class="db-card-head">
            <h2>Harvested per month (tons)</h2>
          </div>
          <div class="db-chart" id="yieldChart"></div>
        </section>
      </div>

      <div class="db-stack">
        <section class="db-card db-wx">
          <div class="db-card-head">
            <h2>Today's weather</h2>
            <a class="db-link" href="weather-forecast.php">Forecast &rarr;</a>
          </div>
          <p class="db-wx-place" id="dashPlace">--</p>
          <p class="db-wx-temp" id="dashTemp">--</p>
          <p class="db-wx-cond" id="dashCondition">Loading...</p>
          <div class="db-wx-stats">
            <div><b id="dashRain">--</b><small>Chance of rain</small></div>
            <div><b id="dashHumidity">--</b><small>Humidity</small></div>
            <div><b id="dashWind">--</b><small>Wind</small></div>
          </div>
        </section>

        <section class="db-card">
          <div class="db-card-head">
            <h2>Up next</h2>
            <a class="db-link" href="harvest-management.php">Harvest plan &rarr;</a>
          </div>
          <div id="nextList"></div>
        </section>

        <section class="db-card">
          <div class="db-card-head">
            <h2>To do</h2>
          </div>
          <ul class="db-tasks" id="tasks"></ul>
        </section>
      </div>
    </div>

    <p class="db-note">Crops, harvests and tasks come from your account in Firebase. Weather is live.</p>
  </main>
</div>

<script type="module" src="../assets/js/auth.js"></script>
<script src="../assets/js/dashboard.js"></script>
<script type="module" src="../assets/js/dashboard-farm.js"></script>
</body>
</html>