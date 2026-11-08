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
$current = 'crop-monitoring.php';

// Shared crop data (also used by harvest-management.php). Edit it in crops-data.php
require __DIR__ . '/crops-data.php';

$today = new DateTime('today');
$totalArea = 0; $attention = 0; $soon = 0;
foreach ($crops as &$c) {
  $totalArea += $c['area'];
  if ($c['status'] === 'warn') $attention++;
  if ($c['status'] === 'soon') $soon++;
  $d = new DateTime($c['harvest']);
  $c['days'] = (int)$today->diff($d)->format('%r%a');
  $c['stageIndex'] = array_search($c['stage'], $stages, true);
  $c['moistureLow'] = $c['moisture'] < 35;
}
unset($c);

$stats = [
  ['Crops monitored', (string)count($crops), 'Across ' . count(array_unique(array_column($crops, 'field'))) . ' fields', 'crop'],
  ['Total area', rtrim(rtrim(number_format($totalArea, 1), '0'), '.') . ' ha', 'Planted this season', 'market'],
  ['Harvest soon', (string)$soon, 'Ready within two weeks', 'harvest'],
  ['Need attention', (string)$attention, $attention ? 'Check the flagged crops' : 'Everything looks fine', 'tips'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriTrack | Crop monitoring</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <style>
    /* Crop monitoring page only. Every class is prefixed cm- so nothing clashes with dashboard.css */
    .cm {
      --ink: #12382a;
      --dark: #0c4a30;
      --green: #1a9d5c;
      --mint: #eef7f1;
      --line: #e1eee6;
      --muted: #6b8a7a;
      color: var(--ink);
      font-variant-numeric: tabular-nums;
    }
    .cm * { box-sizing: border-box; }
    .cm-head { margin-bottom: 24px; }
    .cm-head h1 { margin: 0 0 4px; }
    .cm-head p { margin: 0; color: var(--muted); }
    .cm-head .cm-welcome:empty { display: none; }

    /* Summary */
    .cm-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
    .cm-stat { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 18px 20px; }
    .cm-stat small { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--green); }
    .cm-stat b { display: block; margin: 8px 0 2px; font-size: 1.7rem; font-weight: 800; color: var(--dark); line-height: 1.1; }
    .cm-stat span { font-size: .82rem; color: var(--muted); }
    .cm-stat.warn b { color: #c0392b; }

    /* Filters */
    .cm-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
    .cm-bar h2 { margin: 0; font-size: 1.15rem; color: var(--dark); }
    .cm-filters { display: flex; flex-wrap: wrap; gap: 8px; }
    .cm-filter {
      font: inherit; font-size: .86rem; font-weight: 700; cursor: pointer; padding: 8px 16px; border-radius: 999px;
      background: #fff; color: var(--muted); border: 1px solid var(--line); transition: all .15s;
    }
    .cm-filter:hover { color: var(--dark); border-color: var(--green); }
    .cm-filter.active { background: var(--dark); color: #fff; border-color: var(--dark); }

    /* Crop cards */
    .cm-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .cm-card { background: #fff; border: 1px solid var(--line); border-radius: 20px; padding: 24px; transition: box-shadow .15s, transform .15s; }
    .cm-card:hover { box-shadow: 0 10px 26px rgba(12,74,48,.09); transform: translateY(-2px); }
    .cm-card[hidden] { display: none; }
    .cm-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
    .cm-name { display: flex; align-items: center; gap: 12px; }
    .cm-badge { width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; background: var(--mint); flex: none; }
    .cm-badge svg { width: 22px; height: 22px; fill: none; stroke: var(--green); stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .cm-name b { display: block; font-size: 1.15rem; color: var(--dark); }
    .cm-name small { color: var(--muted); font-size: .84rem; }
    .cm-pill { font-size: .78rem; font-weight: 700; padding: 6px 12px; border-radius: 999px; white-space: nowrap; }
    .cm-pill.ok { background: #e3f4ea; color: #15753f; }
    .cm-pill.soon { background: #fdf2d6; color: #9a6a00; }
    .cm-pill.warn { background: #fde6e3; color: #b13a2f; }

    /* Growth stage tracker */
    .cm-steps { display: grid; grid-template-columns: repeat(5, 1fr); margin: 22px 0 8px; }
    .cm-step { position: relative; text-align: center; font-size: .7rem; font-weight: 600; color: #a6bcb0; padding-top: 22px; }
    .cm-step::before { content: ""; position: absolute; top: 5px; left: 50%; width: 11px; height: 11px; margin-left: -5.5px; border-radius: 50%; background: #dbe9e0; z-index: 1; }
    .cm-step::after { content: ""; position: absolute; top: 10px; left: -50%; width: 100%; height: 2px; background: #dbe9e0; }
    .cm-step:first-child::after { display: none; }
    .cm-step.done { color: var(--muted); }
    .cm-step.done::before, .cm-step.done::after { background: var(--green); }
    .cm-step.now { color: var(--dark); font-weight: 800; }
    .cm-step.now::before { background: var(--green); box-shadow: 0 0 0 5px rgba(26,157,92,.2); }
    .cm-step.now::after { background: var(--green); }

    .cm-prog { display: flex; align-items: center; gap: 12px; margin: 14px 0 20px; }
    .cm-bar-track { flex: 1; height: 8px; border-radius: 99px; background: #e4efe8; overflow: hidden; }
    .cm-bar-track i { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, #4cc088, #1a9d5c); }
    .cm-prog b { font-size: .9rem; color: var(--dark); min-width: 38px; text-align: right; }

    .cm-facts { display: grid; grid-template-columns: repeat(3, 1fr); border-top: 1px solid var(--line); }
    .cm-fact { padding: 16px 14px 4px 0; }
    .cm-fact + .cm-fact { padding-left: 16px; border-left: 1px solid var(--line); }
    .cm-fact small { display: block; font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
    .cm-fact b { display: block; margin-top: 6px; font-size: 1.05rem; color: var(--dark); }
    .cm-fact span { display: block; margin-top: 2px; font-size: .78rem; color: var(--muted); }
    .cm-moist { height: 5px; border-radius: 99px; background: #e0ecf5; margin-top: 8px; overflow: hidden; }
    .cm-moist i { display: block; height: 100%; border-radius: 99px; background: #3a86c8; }
    .cm-moist.low i { background: #d6453d; }

    .cm-action { margin: 18px 0 0; padding: 12px 16px; border-radius: 12px; background: var(--mint); font-size: .88rem; color: #35584a; line-height: 1.5; }
    .cm-action b { color: var(--dark); }
    .cm-card[data-status="warn"] .cm-action { background: #fdeeec; }
    .cm-card[data-status="soon"] .cm-action { background: #fdf6e0; }

    .cm-link { display: inline-block; margin-top: 12px; font-size: .84rem; font-weight: 700; color: var(--green); text-decoration: none; }
    .cm-link:hover { text-decoration: underline; }
    .cm-card:target { box-shadow: 0 0 0 3px rgba(26,157,92,.35); }
    .cm-empty { display: none; padding: 40px; text-align: center; color: var(--muted); background: #fff; border: 1px dashed var(--line); border-radius: 20px; }
    .cm-note { margin-top: 24px; font-size: .84rem; color: var(--muted); }

    @media (max-width: 1100px) {
      .cm-stats { grid-template-columns: repeat(2, 1fr); }
      .cm-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
      .cm-stats { grid-template-columns: 1fr 1fr; gap: 10px; }
      .cm-card { padding: 18px; }
      .cm-facts { grid-template-columns: 1fr; }
      .cm-fact + .cm-fact { padding-left: 0; border-left: 0; border-top: 1px solid var(--line); margin-top: 12px; }
      .cm-step { font-size: .62rem; }
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

  <main class="content cm">
    <header class="cm-head">
      <h1>Crop monitoring</h1>
      <p>Track growth stage, soil moisture and harvest dates for every field.</p>
      <p class="cm-welcome" id="welcome"></p>
    </header>

    <section class="cm-stats">
      <?php foreach ($stats as [$label, $value, $note, $icon]): ?>
        <div class="cm-stat <?= ($label === 'Need attention' && $attention) ? 'warn' : '' ?>">
          <small><?= h($label) ?></small>
          <b><?= h($value) ?></b>
          <span><?= h($note) ?></span>
        </div>
      <?php endforeach; ?>
    </section>

    <div class="cm-bar">
      <h2>Your crops</h2>
      <div class="cm-filters" id="filters" role="group" aria-label="Filter crops">
        <button type="button" class="cm-filter active" data-filter="all">All</button>
        <button type="button" class="cm-filter" data-filter="ok">On track</button>
        <button type="button" class="cm-filter" data-filter="soon">Harvest soon</button>
        <button type="button" class="cm-filter" data-filter="warn">Needs attention</button>
      </div>
    </div>

    <section class="cm-grid" id="cropGrid">
      <?php foreach ($crops as $c): ?>
        <article class="cm-card" id="crop-<?= h($c['id']) ?>" data-status="<?= h($c['status']) ?>">
          <div class="cm-top">
            <div class="cm-name">
              <span class="cm-badge"><svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons['crop'] ?></svg></span>
              <div>
                <b><?= h($c['name']) ?></b>
                <small><?= h($c['field']) ?> &middot; <?= h(rtrim(rtrim(number_format($c['area'], 1), '0'), '.')) ?> ha</small>
              </div>
            </div>
            <span class="cm-pill <?= h($c['status']) ?>"><?= h($c['label']) ?></span>
          </div>

          <div class="cm-steps" aria-label="Growth stage: <?= h($c['stage']) ?>">
            <?php foreach ($stages as $i => $s): ?>
              <div class="cm-step <?= $i < $c['stageIndex'] ? 'done' : ($i === $c['stageIndex'] ? 'now' : '') ?>"><?= h($s) ?></div>
            <?php endforeach; ?>
          </div>

          <div class="cm-prog" title="<?= (int)$c['progress'] ?>% grown">
            <div class="cm-bar-track"><i style="width:<?= (int)$c['progress'] ?>%"></i></div>
            <b><?= (int)$c['progress'] ?>%</b>
          </div>

          <div class="cm-facts">
            <div class="cm-fact">
              <small>Planted</small>
              <b><?= h(date('M j', strtotime($c['planted']))) ?></b>
              <span><?= h(date('Y', strtotime($c['planted']))) ?></span>
            </div>
            <div class="cm-fact">
              <small>Harvest</small>
              <b><?= h(date('M j', strtotime($c['harvest']))) ?></b>
              <span><?= $c['days'] > 0 ? 'In ' . (int)$c['days'] . ' days' : ($c['days'] === 0 ? 'Today' : 'Ready now') ?></span>
            </div>
            <div class="cm-fact">
              <small>Soil moisture</small>
              <b><?= (int)$c['moisture'] ?>%</b>
              <div class="cm-moist <?= $c['moistureLow'] ? 'low' : '' ?>"><i style="width:<?= (int)$c['moisture'] ?>%"></i></div>
            </div>
          </div>

          <p class="cm-action"><b>Next:</b> <?= h($c['action']) ?></p>
          <a class="cm-link" href="harvest-management.php#crop-<?= h($c['id']) ?>">View in harvest plan &rarr;</a>
        </article>
      <?php endforeach; ?>
    </section>
    <div class="cm-empty" id="empty">No crops match this filter.</div>

    <p class="cm-note">Figures shown are sample data until your farm records are connected.</p>
  </main>
</div>

<script type="module" src="../assets/js/auth.js"></script>
<script>
(function () {
  var buttons = document.querySelectorAll('.cm-filter');
  var cards = document.querySelectorAll('.cm-card');
  var empty = document.getElementById('empty');

  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var f = btn.getAttribute('data-filter');
      var shown = 0;
      buttons.forEach(function (b) { b.classList.toggle('active', b === btn); });
      cards.forEach(function (card) {
        var match = f === 'all' || card.getAttribute('data-status') === f;
        card.hidden = !match;
        if (match) shown++;
      });
      empty.style.display = shown ? 'none' : 'block';
    });
  });
})();
</script>
</body>
</html>