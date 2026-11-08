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

// Sample data. Replace with your Firestore/database records later.
$stats = [
  ['Active crops', '6', '2 need attention', 'crop'],
  ['Total area', '12.5 ha', 'Across 4 fields', 'market'],
  ['Next harvest', 'Nov 24', 'Rice, Field A', 'harvest'],
  ['Expected yield', '8.4 t', '+6% vs last season', 'tips'],
];
$crops = [
  ['Rice', 'Field A', 'Flowering', 72, 'On track', 'ok'],
  ['Corn', 'Field B', 'Vegetative', 45, 'On track', 'ok'],
  ['Tomato', 'Field C', 'Fruiting', 88, 'Harvest soon', 'soon'],
  ['Eggplant', 'Field D', 'Seedling', 18, 'Needs water', 'warn'],
];
$yield = ['Jun' => 3.2, 'Jul' => 4.1, 'Aug' => 3.8, 'Sep' => 5.0, 'Oct' => 4.6, 'Nov' => 6.2];
$max = max($yield);
$tasks = [
  ['Water Field D', 'Today'],
  ['Apply fertilizer, Field B', 'Tomorrow'],
  ['Inspect rice flowering', 'Friday'],
  ['Book transport for tomatoes', 'Saturday'],
];
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
</head>
<body>
<div class="app">
  <aside class="side">
    <div class="brand"><span class="brand-mark"><?= ic('leaf') ?></span>AgriTrack</div>
    <nav class="menu" aria-label="Main">
      <?php foreach ($nav as [$href, $label, $icon]): ?>
        <a href="<?= $href ?>" <?= $href === 'dashboard.php' ? 'class="active" aria-current="page"' : '' ?>><?= ic($icon) ?><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
    <button class="signout" id="logoutBtn" type="button">Sign out</button>
  </aside>

  <main class="content">
    <header class="head">
      <div>
        <h1 id="welcome">Loading...</h1>
        <p><?= date('l, F j') ?>. Here is how your farm is doing today.</p>
      </div>
    </header>

    <section class="stats">
      <?php foreach ($stats as [$label, $value, $note, $icon]): ?>
        <div class="stat">
          <span class="chip"><?= ic($icon) ?></span>
          <p class="muted"><?= $label ?></p>
          <strong><?= $value ?></strong>
          <small><?= $note ?></small>
        </div>
      <?php endforeach; ?>
    </section>

    <div class="cols">
      <div class="stack">
        <section class="panel">
          <h2>Crop progress</h2>
          <?php foreach ($crops as [$name, $field, $stage, $pct, $status, $cls]): ?>
            <div class="crop">
              <div><b><?= $name ?></b><small class="muted"><?= $field ?> &middot; <?= $stage ?></small></div>
              <div class="bar" title="<?= $pct ?>% grown"><i style="width:<?= $pct ?>%"></i></div>
              <span class="pill <?= $cls ?>"><?= $status ?></span>
            </div>
          <?php endforeach; ?>
        </section>

        <section class="panel">
          <h2>Yield per month (tons)</h2>
          <div class="chart">
            <?php foreach ($yield as $m => $v): ?>
              <div class="col"><em><?= $v ?></em><span style="height:<?= round($v / $max * 100) ?>%"></span><small><?= $m ?></small></div>
            <?php endforeach; ?>
          </div>
        </section>
      </div>

      <div class="stack">
        <section class="panel weather">
          <h2>Today's weather</h2>
          <div class="temp">29&deg;C</div>
          <p>Partly cloudy</p>
          <ul>
            <li><b>40%</b> chance of rain</li>
            <li><b>78%</b> humidity</li>
            <li><b>12 km/h</b> wind</li>
          </ul>
        </section>

        <section class="panel">
          <h2>To do</h2>
          <ul class="tasks">
            <?php foreach ($tasks as $i => [$t, $when]): ?>
              <li><input type="checkbox" id="t<?= $i ?>"><label for="t<?= $i ?>"><?= $t ?></label><small class="muted"><?= $when ?></small></li>
            <?php endforeach; ?>
          </ul>
        </section>
      </div>
    </div>
    <p class="note muted">Figures shown are sample data until your farm records are connected.</p>
  </main>
</div>
<script type="module" src="../assets/js/auth.js"></script>
</body>
</html>