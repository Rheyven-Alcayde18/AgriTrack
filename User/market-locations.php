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
$current = 'market-locations.php';
// Crops, markets and saved markets now come from Firebase (see assets/js/market-locations.js).
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
    .ml [hidden] { display: none !important; }
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
    .ml-empty b { display: block; color: var(--dark); font-size: 1.05rem; margin-bottom: 6px; }
    .ml-empty a { color: var(--green); font-weight: 700; }
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

    <div class="ml-empty" id="noCrops">
      <b>No crops to sell yet</b>
      Add a crop that is still growing, then come back to see which markets pay the most for it.<br>
      <a href="crop-monitoring.php">Go to Crop monitoring &rarr;</a>
    </div>

    <div id="mlBody">
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
    </div>
  </main>
</div>

<script type="module" src="../assets/js/auth.js"></script>
<script type="module" src="../assets/js/market-locations.js"></script>
</body>
</html>