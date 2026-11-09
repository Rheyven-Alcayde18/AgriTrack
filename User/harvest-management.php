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

$nav = [
  ['dashboard.php', 'Dashboard', 'home'],
  ['crop-monitoring.php', 'Crop monitoring', 'crop'],
  ['weather-forecast.php', 'Weather forecast', 'weather'],
  ['harvest-management.php', 'Harvest management', 'harvest'],
  ['market-locations.php', 'Market locations', 'market'],
  ['recommendations.php', 'Recommendations', 'tips'],
];
$current = 'harvest-management.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriTrack | Harvest management</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="../assets/css/agri-ui.css">
  <style>
    /* Harvest page only. Every class is prefixed hm- so nothing clashes with dashboard.css */
    .hm {
      --ink: #12382a; --dark: #0c4a30; --green: #1a9d5c; --mint: #eef7f1;
      --line: #e1eee6; --muted: #6b8a7a; --amber: #e0a000; --slate: #7f9c8e;
      color: var(--ink); font-variant-numeric: tabular-nums;
    }
    .hm * { box-sizing: border-box; }
    .hm-head { margin-bottom: 24px; }
    .hm-head h1 { margin: 0 0 4px; }
    .hm-head p { margin: 0; color: var(--muted); }
    .hm-head .hm-welcome:empty { display: none; }
    .hm-card { background: #fff; border: 1px solid var(--line); border-radius: 20px; }
    .hm-card h2 { margin: 0; font-size: 1.1rem; color: var(--dark); }

    /* Stats */
    .hm-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 16px; }
    .hm-stat { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 18px 20px; }
    .hm-stat small { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--green); }
    .hm-stat b { display: block; margin: 8px 0 2px; font-size: 1.6rem; font-weight: 800; color: var(--dark); line-height: 1.1; }
    .hm-stat span { font-size: .82rem; color: var(--muted); }

    /* Timeline */
    .hm-tlcard { padding: 22px 26px 6px; margin-bottom: 16px; }
    .hm-tlcard p { margin: 4px 0 0; font-size: .86rem; color: var(--muted); }
    .hm-track { position: relative; height: 6px; margin: 74px 44px 66px; border-radius: 99px; background: #e4efe8; }
    .hm-mark { position: absolute; top: -5px; transform: translateX(-50%); text-decoration: none; color: inherit; }
    .hm-mark i { display: block; width: 16px; height: 16px; border-radius: 50%; border: 3px solid #fff; background: var(--green); box-shadow: 0 0 0 2px var(--green); transition: transform .15s; }
    .hm-mark:hover i { transform: scale(1.25); }
    .hm-mark span { position: absolute; top: 26px; left: 50%; transform: translateX(-50%); width: 92px; text-align: center; line-height: 1.3; }
    .hm-mark.up span { top: auto; bottom: 28px; }
    .hm-mark b { display: block; font-size: .86rem; color: var(--dark); }
    .hm-mark small { font-size: .74rem; color: var(--muted); }
    .hm-mark.soon i { background: var(--amber); box-shadow: 0 0 0 2px var(--amber); }
    .hm-mark.growing i { background: var(--slate); box-shadow: 0 0 0 2px var(--slate); }
    .hm-mark.today i { background: #fff; box-shadow: 0 0 0 2px var(--dark); border-color: var(--dark); }
    .hm-legend { display: flex; flex-wrap: wrap; gap: 16px; padding: 0 0 18px; font-size: .78rem; color: var(--muted); }
    .hm-legend em { font-style: normal; display: inline-flex; align-items: center; gap: 6px; }
    .hm-legend em::before { content: ""; width: 9px; height: 9px; border-radius: 50%; background: var(--green); }
    .hm-legend em.soon::before { background: var(--amber); }
    .hm-legend em.growing::before { background: var(--slate); }

    /* Layout */
    .hm-cols { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); gap: 16px; margin-bottom: 16px; align-items: start; }
    .hm-sched, .hm-prep, .hm-past { padding: 22px 24px; }

    /* Schedule rows */
    .hm-row { display: grid; grid-template-columns: 58px minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 18px 0; border-top: 1px solid var(--line); }
    .hm-row:first-of-type { border-top: 0; }
    .hm-row:target { background: var(--mint); margin: 0 -12px; padding: 18px 12px; border-radius: 14px; border-top-color: transparent; }
    .hm-date { text-align: center; border-radius: 14px; background: var(--mint); padding: 8px 0; }
    .hm-date small { display: block; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--green); }
    .hm-date b { font-size: 1.4rem; color: var(--dark); line-height: 1.1; }
    .hm-info b { display: block; font-size: 1.02rem; color: var(--dark); }
    .hm-info small { color: var(--muted); font-size: .82rem; }
    .hm-ready { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
    .hm-ready .tr { flex: 1; height: 6px; border-radius: 99px; background: #e4efe8; overflow: hidden; }
    .hm-ready .tr i { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, #4cc088, #1a9d5c); }
    .hm-ready span { font-size: .78rem; color: var(--muted); white-space: nowrap; }
    .hm-side { text-align: right; }
    .hm-pill { display: inline-block; font-size: .76rem; font-weight: 700; padding: 5px 12px; border-radius: 999px; }
    .hm-pill.soon { background: #fdf2d6; color: #9a6a00; }
    .hm-pill.upcoming { background: #e3f4ea; color: #15753f; }
    .hm-pill.growing { background: #edf1ef; color: #5b756a; }
    .hm-side b { display: block; margin-top: 8px; font-size: 1rem; color: var(--dark); }
    .hm-side small { display: block; color: var(--muted); font-size: .78rem; }
    .hm-link { display: inline-block; margin-top: 6px; font-size: .8rem; font-weight: 700; color: var(--green); text-decoration: none; }
    .hm-link:hover { text-decoration: underline; }
    .hm-link + .hm-link { display: block; margin-top: 2px; }

    /* Prep checklist */
    .hm-prep h2 { margin-bottom: 4px; }
    .hm-prep > p { margin: 0 0 12px; font-size: .84rem; color: var(--muted); }
    .hm-group { padding: 14px 0; border-top: 1px solid var(--line); }
    .hm-group h3 { margin: 0 0 8px; font-size: .92rem; color: var(--dark); display: flex; justify-content: space-between; gap: 8px; }
    .hm-group h3 small { font-weight: 600; color: var(--muted); font-size: .76rem; }
    .hm-group label { display: flex; align-items: center; gap: 10px; padding: 6px 0; font-size: .9rem; cursor: pointer; }
    .hm-group input { width: 17px; height: 17px; accent-color: var(--green); flex: none; }
    .hm-group input:checked + span { color: var(--muted); text-decoration: line-through; }

    /* Past harvests */
    .hm-past { margin-bottom: 8px; }
    .hm-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: .92rem; }
    .hm-table th { text-align: left; font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); padding: 10px 8px; border-bottom: 1px solid var(--line); }
    .hm-table td { padding: 14px 8px; border-bottom: 1px solid var(--line); color: var(--ink); }
    .hm-table td:first-child { color: var(--muted); }
    .hm-table td b { color: var(--dark); }
    .hm-table tfoot td { border-bottom: 0; font-weight: 800; color: var(--dark); }
    .hm-table .r { text-align: right; }
    .hm-note { margin-top: 18px; font-size: .84rem; color: var(--muted); }

    @media (max-width: 1100px) {
      .hm-stats { grid-template-columns: repeat(2, 1fr); }
      .hm-cols { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
      .hm-track { margin: 74px 36px 66px; }
      .hm-mark span { width: 70px; }
      .hm-mark b { font-size: .78rem; }
      .hm-sched, .hm-prep, .hm-past, .hm-tlcard { padding: 18px 16px; }
      .hm-row { grid-template-columns: 52px minmax(0, 1fr); }
      .hm-side { grid-column: 1 / -1; text-align: left; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
      .hm-side b { margin-top: 0; }
      .hm-table { font-size: .84rem; }
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

  <main class="content hm">
    <header class="hm-head">
      <h1>Harvest management</h1>
      <p>Plan upcoming harvests from your crop monitoring records.</p>
      <p class="hm-welcome" id="welcome"></p>
    </header>

    <section class="hm-stats" id="stats" aria-live="polite"></section>

    <section class="hm-card hm-tlcard">
      <h2>Harvest timeline</h2>
      <p>Expected harvest dates for the coming months.</p>
      <div class="hm-track" id="track"></div>
      <div class="hm-legend">
        <em class="soon">Within 2 weeks</em>
        <em>Within 45 days</em>
        <em class="growing">Still growing</em>
      </div>
    </section>

    <div class="hm-cols">
      <section class="hm-card hm-sched">
        <h2>Harvest schedule</h2>
        <div id="sched"><div class="ag-loading">Loading&hellip;</div></div>
      </section>

      <aside class="hm-card hm-prep">
        <h2>Preparation checklist</h2>
        <p>Tasks for your next harvests. Ticks are saved to your account.</p>
        <div id="prep"></div>
      </aside>
    </div>

    <section class="hm-card hm-past">
      <h2>Past harvests</h2>
      <table class="hm-table">
        <thead>
          <tr><th>Date</th><th>Crop</th><th>Field</th><th class="r">Yield</th><th class="r">Sold at</th><th class="r">Revenue</th></tr>
        </thead>
        <tbody id="past"></tbody>
        <tfoot id="pastFoot"></tfoot>
      </table>
    </section>

    <p class="hm-note">Crop records come from Crop monitoring and are saved in Firebase. Record a harvest from the crop card.</p>
  </main>
</div>

<script type="module" src="../assets/js/auth.js"></script>
<script type="module" src="../assets/js/harvest.js"></script>
</body>
</html>