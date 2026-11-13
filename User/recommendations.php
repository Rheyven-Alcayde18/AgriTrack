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
// Crops, markets and ticked items now come from Firebase (see assets/js/recommendations-page.js).
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
      Recommendations are generated from the crops you added, today's Open-Meteo forecast and the market listings. They are general guidance, so adjust them to your own field conditions.
      Completed items reset each day. Market listings and prices are sample data.
    </p>
  </main>
</div>

<script type="module" src="../assets/js/auth.js"></script>
<script type="module" src="../assets/js/recommendations.js"></script>
<script src="../assets/js/chatbot.js"></script>
</body>
</html>