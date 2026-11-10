<?php
$icons = [
  'home'     => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
  'farmers'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17.5" cy="9" r="2.5"/><path d="M17 14.2c2.6.2 4.5 2.2 4.5 5.3"/>',
  'weather'  => '<path d="M17.5 19a4.5 4.5 0 1 0-1-8.9A6 6 0 0 0 4.7 12 3.5 3.5 0 0 0 6.5 19Z"/>',
  'reports'  => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/>',
  'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
  'leaf'     => '<path d="M6 18c0-8 5-13 13-13 0 8-5 13-13 13Z"/><path d="M6 18 14 10"/>',
  'bell'     => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
  'revenue'  => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'
];
function ic($k) { global $icons; return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[$k] . '</svg>'; }

$nav = [
  ['dashboard.php', 'Dashboard', 'home'],
  ['manage-farmers.php', 'Manage farmers', 'farmers'],
  ['weather-forecast.php', 'Weather forecast', 'weather'],
  ['reports.php', 'Reports', 'reports'],
  ['settings.php', 'Settings', 'settings'],
];
$current = 'dashboard.php';
// All figures come from Firebase (see assets/js/admin-dashboard.js).
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriTrack | Admin dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <style>
    /* Admin dashboard styling */
    .ad {
      --ink: #12382a; --dark: #0c4a30; --green: #1a9d5c; --mint: #eef7f1;
      --line: #e1eee6; --muted: #6b8a7a; --amber: #d99a00; --red: #d6453d; --blue: #3a86c8;
      color: var(--ink); font-variant-numeric: tabular-nums;
    }
    .ad * { box-sizing: border-box; }
    .ad-head-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 22px; flex-wrap: wrap; }
    .ad-head h1 { margin: 0 0 4px; }
    .ad-head p { margin: 0; color: var(--muted); }
    .ad-head .ad-welcome:empty { display: none; }
    .ad-actions { display: flex; gap: 10px; align-items: center; }

    .ad-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px; border-radius: 12px; font-size: .84rem; font-weight: 700; border: none; cursor: pointer; text-decoration: none; transition: all .15s ease; }
    .ad-btn-primary { background: var(--dark); color: #fff; }
    .ad-btn-primary:hover { background: var(--green); }
    .ad-btn-outline { background: #fff; border: 1px solid var(--line); color: var(--dark); }
    .ad-btn-outline:hover { background: var(--mint); border-color: var(--green); }
    .ad-btn svg { width: 16px; height: 16px; }

    .ad-sample { display: none; margin-top: 12px; padding: 8px 14px; border-radius: 999px; background: #fdf6e0; color: #7a5a00; font-size: .82rem; font-weight: 600; width: fit-content; }

    .ad-card { background: #fff; border: 1px solid var(--line); border-radius: 20px; padding: 22px 24px; }
    .ad-card h2 { margin: 0 0 2px; font-size: 1.05rem; color: var(--dark); }
    .ad-card > p { margin: 0 0 16px; font-size: .84rem; color: var(--muted); }
    .ad-card .ad-link { float: right; font-size: .84rem; font-weight: 700; color: var(--green); text-decoration: none; }
    .ad-card .ad-link:hover { text-decoration: underline; }

    /* Stats Grid with Revenue Highlight */
    .ad-stats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 16px; }
    .ad-stat { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 18px 20px; }
    .ad-stat small { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--green); }
    .ad-stat b { display: block; margin: 8px 0 2px; font-size: 1.6rem; font-weight: 800; color: var(--dark); line-height: 1.1; }
    .ad-stat span { font-size: .82rem; color: var(--muted); }
    .ad-stat.warn b { color: var(--red); }
    .ad-stat.featured { background: linear-gradient(135deg, #1a9d5c 0%, #0c4a30 100%); color: #fff; }
    .ad-stat.featured small { color: #a4f4cd; }
    .ad-stat.featured b { color: #fff; }
    .ad-stat.featured span { color: #c4ecd8; }

    /* Layout */
    .ad-grid { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 16px; margin-bottom: 16px; align-items: start; }
    .ad-grid.even { grid-template-columns: repeat(2, minmax(0, 1fr)); }

    /* Monthly chart */
    .ad-chart { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; align-items: end; height: 190px; padding-top: 10px; }
    .ad-col { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 6px; }
    .ad-col i { display: block; width: 100%; max-width: 46px; border-radius: 10px 10px 4px 4px; background: linear-gradient(180deg, #4cc088, #1a9d5c); min-height: 4px; }
    .ad-col.zero i { background: #e4efe8; }
    .ad-col b { font-size: .8rem; color: var(--dark); }
    .ad-col small { font-size: .74rem; color: var(--muted); }

    /* Horizontal bars */
    .ad-bars { display: grid; gap: 12px; }
    .ad-bar-top { display: flex; justify-content: space-between; gap: 10px; font-size: .86rem; margin-bottom: 5px; }
    .ad-bar-top b { color: var(--dark); }
    .ad-bar-top span { color: var(--muted); }
    .ad-track { height: 8px; border-radius: 99px; background: #e4efe8; overflow: hidden; }
    .ad-track i { display: block; height: 100%; border-radius: 99px; background: var(--green); }
    .ad-track.blue i { background: var(--blue); }

    /* Lists */
    .ad-list { display: grid; gap: 0; }
    .ad-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 0; border-top: 1px solid var(--line); }
    .ad-row:first-child { border-top: 0; padding-top: 2px; }
    .ad-row b { display: block; font-size: .92rem; color: var(--dark); }
    .ad-row small { display: block; font-size: .8rem; color: var(--muted); margin-top: 2px; line-height: 1.4; }
    .ad-row > span { font-size: .78rem; color: var(--muted); white-space: nowrap; }
    .ad-pill { font-size: .74rem; font-weight: 700; padding: 4px 11px; border-radius: 999px; white-space: nowrap; }
    .ad-pill.active, .ad-pill.ok { background: #e3f4ea; color: #15753f; }
    .ad-pill.inactive, .ad-pill.done { background: #edf1ef; color: #5b756a; }
    .ad-pill.soon { background: #fdf2d6; color: #9a6a00; }
    .ad-pill.warn { background: #fde6e3; color: #b13a2f; }
    .ad-none { margin: 0; padding: 18px 0 6px; text-align: center; color: var(--muted); font-size: .9rem; }

    .ad-empty { display: none; margin-bottom: 16px; padding: 36px 24px; text-align: center; color: var(--muted); background: #fff; border: 1px dashed var(--line); border-radius: 20px; }
    .ad-empty b { display: block; color: var(--dark); font-size: 1.1rem; margin-bottom: 6px; }
    .ad-empty a { color: var(--green); font-weight: 700; }

    @media (max-width: 1200px) {
      .ad-stats { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 1100px) {
      .ad-grid, .ad-grid.even { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
      .ad-stats { grid-template-columns: repeat(1, 1fr); gap: 10px; }
      .ad-card { padding: 18px; }
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

  <main class="content ad">
    <div class="ad-head-row">
      <header class="ad-head">
        <h1>Admin dashboard</h1>
        <p>An overview of every farmer, crop and harvest on AgriTrack.</p>
        <p class="ad-welcome" id="welcome"></p>
        <span class="ad-sample" id="sampleNote">Some of these figures come from sample farmers. Remove them in Manage farmers when you are done.</span>
      </header>

      <!-- Quick Action Buttons -->
      <div class="ad-actions">
        <a href="weather-forecast.php?action=broadcast" class="ad-btn ad-btn-outline">
          <?= ic('bell') ?> Send Weather Alert
        </a>
        <a href="reports.php" class="ad-btn ad-btn-primary">
          <?= ic('reports') ?> Generate Report
        </a>
      </div>
    </div>

    <div class="ad-empty" id="empty">
      <b>No farmers yet</b>
      Farmers show up here once they register. To try the dashboard first, open
      <a href="manage-farmers.php">Manage farmers</a> and choose Load 12 sample farmers.
    </div>

    <!-- Stats Section with Featured Projected Revenue Card -->
    <section class="ad-stats" id="stats">
      <!-- Standard stats rendered by JS; Projected Revenue featured card inserted below for instant visibility -->
      <div class="ad-stat featured">
        <small>PROJECTED REVENUE</small>
        <b id="totalRevenue">₱2,412,000</b>
        <span>Estimated next harvest</span>
      </div>
    </section>

    <div class="ad-grid">
      <section class="ad-card">
        <h2>Harvested per month (tons)</h2>
        <p>Last six months, all farmers combined.</p>
        <div class="ad-chart" id="chart"></div>
      </section>
      <section class="ad-card">
        <a class="ad-link" href="manage-farmers.php">Manage &rarr;</a>
        <h2>Recently joined</h2>
        <p>The newest registered farmers.</p>
        <div class="ad-list" id="joined"></div>
      </section>
    </div>

    <div class="ad-grid even">
      <section class="ad-card">
        <h2>Farmers by municipality</h2>
        <p>Where your farmers are based.</p>
        <div class="ad-bars" id="towns"></div>
      </section>
      <section class="ad-card">
        <h2>Crops being grown</h2>
        <p>Crops still in the field, by area planted.</p>
        <div class="ad-bars" id="mix"></div>
      </section>
    </div>

    <div class="ad-grid even">
      <section class="ad-card">
        <h2>Needs attention</h2>
        <p>Crops that are dry or almost ready to harvest.</p>
        <div class="ad-list" id="attention"></div>
      </section>
      <section class="ad-card">
        <h2>Recent activity</h2>
        <p>The latest logged actions across all farms.</p>
        <div class="ad-list" id="activity"></div>
      </section>
    </div>
  </main>
</div>

<script type="module" src="../assets/js/auth.js"></script>
<script type="module" src="../assets/js/admin-dashboard.js"></script>
</body>
</html>