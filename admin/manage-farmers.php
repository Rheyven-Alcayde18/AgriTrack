<?php
$icons = [
  'home'     => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
  'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8"/><path d="M18 14.3c2.2.7 3.5 2.6 3.5 5.7"/>',
  'crop'     => '<path d="M7 20h10"/><path d="M12 20v-9"/><path d="M12 11c0-3 2-5 6-5 0 4-2 6-6 5Z"/><path d="M12 13c0-2.5-1.8-4.5-5.5-4.5 0 3.5 2 5 5.5 4.5Z"/>',
  'reports'  => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-8"/><path d="M22 20H2"/>',
  'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
  'leaf'     => '<path d="M6 18c0-8 5-13 13-13 0 8-5 13-13 13Z"/><path d="M6 18 14 10"/>',
  'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
];
function ic($k) { global $icons; return '<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">' . $icons[$k] . '</svg>'; }

$nav = [
  ['dashboard.php', 'Dashboard', 'home'],
  ['manage-farmers.php', 'Manage farmers', 'users'],
  ['manage-crops.php', 'Manage crops', 'crop'],
  ['reports.php', 'Reports', 'reports'],
  ['settings.php', 'Settings', 'settings'],
];
$current = 'manage-farmers.php';

require __DIR__ . '/farmers-data.php';
// Market names come from the same list the farmer pages use
require __DIR__ . '/../user/markets-data.php';
$marketInfo = [];
foreach ($markets as $m) {
  $marketInfo[$m['id']] = ['name' => $m['name'], 'town' => $m['town'], 'type' => $m['type']];
}

$payload = json_encode(['farmers' => $farmers, 'markets' => $marketInfo],
  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriTrack Admin | Manage farmers</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <style>
    /* Farmer management only. Every class is prefixed mf- so nothing clashes with dashboard.css */
    .mf, .mf-drawer {
      --ink: #12382a; --dark: #0c4a30; --green: #1a9d5c; --mint: #eef7f1;
      --line: #e1eee6; --muted: #6b8a7a; --amber: #d99a00; --red: #d6453d;
      color: var(--ink); font-variant-numeric: tabular-nums;
    }
    .mf *, .mf-drawer * { box-sizing: border-box; }
    .mf-head { margin-bottom: 22px; }
    .mf-head h1 { margin: 0 0 4px; }
    .mf-head p { margin: 0; color: var(--muted); }
    .mf-head .mf-welcome:empty { display: none; }
    .mf-card { background: #fff; border: 1px solid var(--line); border-radius: 20px; }

    /* Stats */
    .mf-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 16px; }
    .mf-stat { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 18px 20px; }
    .mf-stat small { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--green); }
    .mf-stat b { display: block; margin: 8px 0 2px; font-size: 1.7rem; font-weight: 800; color: var(--dark); line-height: 1.1; }
    .mf-stat span { font-size: .82rem; color: var(--muted); }

    /* Controls */
    .mf-controls { padding: 18px 22px; margin-bottom: 14px; display: grid; gap: 14px; }
    .mf-row { display: flex; flex-wrap: wrap; gap: 12px 16px; align-items: flex-end; }
    .mf-field { display: flex; flex-direction: column; gap: 6px; min-width: 150px; }
    .mf-field.grow { flex: 1; min-width: 220px; }
    .mf-field label { font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
    .mf-search { position: relative; display: flex; align-items: center; }
    .mf-search svg { position: absolute; left: 13px; width: 17px; height: 17px; fill: none; stroke: var(--muted); stroke-width: 2; stroke-linecap: round; pointer-events: none; }
    .mf-search input { padding-left: 38px !important; }
    .mf input[type="text"], .mf input[type="email"], .mf input[type="tel"], .mf input[type="number"], .mf select,
    .mf-drawer input[type="text"], .mf-drawer input[type="email"], .mf-drawer input[type="tel"], .mf-drawer input[type="number"] {
      font: inherit; font-size: .92rem; padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px; background: #fff; color: var(--ink); outline: none; width: 100%;
    }
    .mf input:focus, .mf select:focus, .mf-drawer input:focus { border-color: var(--green); box-shadow: 0 0 0 4px rgba(26,157,92,.12); }
    .mf-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .mf-pill { font: inherit; font-size: .84rem; font-weight: 700; cursor: pointer; padding: 8px 15px; border-radius: 999px; background: #fff; color: var(--muted); border: 1px solid var(--line); transition: all .15s; }
    .mf-pill:hover { color: var(--dark); border-color: var(--green); }
    .mf-pill.active { background: var(--dark); color: #fff; border-color: var(--dark); }
    .mf-pill i { font-style: normal; margin-left: 6px; opacity: .7; }
    .mf-count { font-size: .86rem; color: var(--muted); margin: 0 2px 10px; }

    /* Table */
    .mf-table-wrap { overflow-x: auto; }
    .mf-table { width: 100%; border-collapse: collapse; min-width: 860px; }
    .mf-table th { text-align: left; font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); padding: 14px 16px; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .mf-table td { padding: 14px 16px; border-bottom: 1px solid var(--line); vertical-align: middle; font-size: .9rem; }
    .mf-table tbody tr:last-child td { border-bottom: 0; }
    .mf-table tbody tr { cursor: pointer; transition: background .12s; }
    .mf-table tbody tr:hover { background: #f8fcfa; }
    .mf-table tr.off td:not(.mf-act) { opacity: .62; }
    .mf-who { display: flex; align-items: center; gap: 12px; min-width: 210px; }
    .mf-av { width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center; flex: none; background: var(--mint); color: var(--dark); font-weight: 800; font-size: .86rem; }
    .mf-who b { display: block; color: var(--dark); }
    .mf-who small { color: var(--muted); font-size: .8rem; }
    .mf-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .mf-chip { font-size: .74rem; font-weight: 700; padding: 4px 10px; border-radius: 8px; background: #f2f7f4; color: #4d6b5d; white-space: nowrap; }
    .mf-tag { font-size: .76rem; font-weight: 700; padding: 5px 12px; border-radius: 999px; white-space: nowrap; display: inline-block; }
    .mf-tag.active, .mf-tag.ok { background: #e3f4ea; color: #15753f; }
    .mf-tag.inactive { background: #edf1ef; color: #5b756a; }
    .mf-tag.soon { background: #fdf2d6; color: #9a6a00; }
    .mf-tag.warn { background: #fde6e3; color: #b13a2f; }
    .mf-tag.done { background: #e2eefa; color: #2a62a0; }
    .mf-act { white-space: nowrap; text-align: right; }
    .mf-btn { font: inherit; font-size: .82rem; font-weight: 700; padding: 8px 14px; border-radius: 10px; border: 1px solid var(--line); background: #fff; color: var(--dark); cursor: pointer; transition: all .15s; }
    .mf-btn:hover { border-color: var(--green); color: var(--green); }
    .mf-btn.primary { background: var(--dark); color: #fff; border-color: var(--dark); }
    .mf-btn.primary:hover { background: var(--green); border-color: var(--green); color: #fff; }
    .mf-btn.danger { color: #b13a2f; }
    .mf-btn.danger:hover { border-color: var(--red); color: var(--red); }
    .mf-empty { display: none; padding: 44px 20px; text-align: center; color: var(--muted); }
    .mf-foot { margin-top: 20px; font-size: .84rem; color: var(--muted); line-height: 1.6; }
    .mf-foot button { font: inherit; font-size: .84rem; background: none; border: 0; padding: 0; color: var(--green); font-weight: 700; cursor: pointer; text-decoration: underline; }

    /* Profile drawer */
    .mf-overlay { position: fixed; inset: 0; background: rgba(8, 40, 26, .45); z-index: 50; display: none; }
    .mf-overlay.open { display: block; }
    .mf-drawer { position: fixed; top: 0; right: 0; bottom: 0; width: min(580px, 100%); background: #fff; z-index: 51; display: none; flex-direction: column; box-shadow: -12px 0 40px rgba(0,0,0,.18); }
    .mf-drawer.open { display: flex; }
    .mf-d-head { padding: 24px 26px 0; border-bottom: 1px solid var(--line); }
    .mf-d-top { display: flex; align-items: center; gap: 14px; }
    .mf-d-top .mf-av { width: 54px; height: 54px; font-size: 1.05rem; }
    .mf-d-top h2 { margin: 0; font-size: 1.25rem; color: var(--dark); }
    .mf-d-top p { margin: 2px 0 0; font-size: .86rem; color: var(--muted); }
    .mf-close { margin-left: auto; font: inherit; font-size: 1.5rem; line-height: 1; width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--line); background: #fff; color: var(--muted); cursor: pointer; }
    .mf-close:hover { color: var(--dark); border-color: var(--green); }
    .mf-d-actions { display: flex; flex-wrap: wrap; gap: 10px; margin: 16px 0; }
    .mf-tabs { display: flex; gap: 4px; overflow-x: auto; }
    .mf-tab { font: inherit; font-size: .86rem; font-weight: 700; padding: 11px 14px; background: none; border: 0; border-bottom: 3px solid transparent; color: var(--muted); cursor: pointer; white-space: nowrap; }
    .mf-tab:hover { color: var(--dark); }
    .mf-tab.active { color: var(--dark); border-bottom-color: var(--green); }
    .mf-d-body { padding: 22px 26px 30px; overflow-y: auto; flex: 1; }
    .mf-d-body h3 { margin: 0 0 12px; font-size: 1rem; color: var(--dark); }
    .mf-kv { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; margin-bottom: 20px; }
    .mf-kv div { padding: 14px 16px; border-bottom: 1px solid var(--line); }
    .mf-kv div:nth-child(odd) { border-right: 1px solid var(--line); }
    .mf-kv div:nth-last-child(-n+2) { border-bottom: 0; }
    .mf-kv small { display: block; font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
    .mf-kv b { display: block; margin-top: 5px; color: var(--dark); font-size: .95rem; word-break: break-word; }
    .mf-mini { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
    .mf-mini div { background: var(--mint); border-radius: 14px; padding: 14px; }
    .mf-mini small { display: block; font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--green); }
    .mf-mini b { display: block; margin-top: 5px; font-size: 1.2rem; color: var(--dark); }
    .mf-item { border: 1px solid var(--line); border-radius: 16px; padding: 16px 18px; margin-bottom: 12px; }
    .mf-item-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .mf-item b { color: var(--dark); }
    .mf-item small { color: var(--muted); font-size: .8rem; }
    .mf-bar { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
    .mf-bar .tr { flex: 1; height: 7px; border-radius: 99px; background: #e4efe8; overflow: hidden; }
    .mf-bar .tr i { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, #4cc088, #1a9d5c); }
    .mf-bar span { font-size: .8rem; font-weight: 700; color: var(--dark); min-width: 36px; text-align: right; }
    .mf-dates { display: flex; flex-wrap: wrap; gap: 4px 18px; margin-top: 10px; font-size: .82rem; color: var(--muted); }
    .mf-time { list-style: none; margin: 0; padding: 0 0 0 4px; }
    .mf-time li { position: relative; padding: 0 0 18px 24px; border-left: 2px solid var(--line); margin-left: 5px; }
    .mf-time li::before { content: ""; position: absolute; left: -6px; top: 3px; width: 10px; height: 10px; border-radius: 50%; background: var(--green); box-shadow: 0 0 0 3px #fff; }
    .mf-time li:last-child { border-left-color: transparent; padding-bottom: 0; }
    .mf-time small { display: block; color: var(--muted); font-size: .78rem; margin-bottom: 2px; }
    .mf-htable { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .mf-htable th { text-align: left; font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); padding: 8px 6px; border-bottom: 1px solid var(--line); }
    .mf-htable td { padding: 12px 6px; border-bottom: 1px solid var(--line); }
    .mf-htable .r { text-align: right; }
    .mf-htable tfoot td { border-bottom: 0; font-weight: 800; color: var(--dark); }
    .mf-none { padding: 26px 16px; text-align: center; color: var(--muted); background: #fafcfb; border: 1px dashed var(--line); border-radius: 14px; font-size: .9rem; }
    .mf-banner { margin-bottom: 18px; padding: 12px 16px; border-radius: 12px; background: #eef2f0; color: #4d6b5d; font-size: .86rem; }
    .mf-banner.good { background: #e3f4ea; color: #15753f; }

    /* Edit form */
    .mf-form { display: grid; gap: 14px; }
    .mf-form .two { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .mf-drawer input:disabled { background: #f4f8f6; color: var(--muted); }
    .mf-err { font-size: .78rem; color: var(--red); min-height: 1em; }
    .mf-form-actions { display: flex; gap: 10px; margin-top: 4px; }

    @media (max-width: 1100px) { .mf-stats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px) {
      .mf-controls { padding: 16px; }
      .mf-field { min-width: 100%; }
      .mf-d-head, .mf-d-body { padding-left: 18px; padding-right: 18px; }
      .mf-kv { grid-template-columns: 1fr; }
      .mf-kv div:nth-child(odd) { border-right: 0; }
      .mf-kv div:nth-last-child(2) { border-bottom: 1px solid var(--line); }
      .mf-mini { grid-template-columns: 1fr; }
      .mf-form .two { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<div class="app">
  <aside class="side">
    <div class="brand"><span class="brand-mark"><?= ic('leaf') ?></span>AgriTrack Admin</div>
    <nav class="menu" aria-label="Admin">
      <?php foreach ($nav as [$href, $label, $icon]): ?>
        <a href="<?= $href ?>" <?= $href === $current ? 'class="active" aria-current="page"' : '' ?>><?= ic($icon) ?><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
    <button class="signout" id="logoutBtn" type="button">Sign out</button>
  </aside>

  <main class="content mf">
    <header class="mf-head">
      <h1>Manage farmers</h1>
      <p>View registered farmers, open their profiles and manage their accounts.</p>
      <p class="mf-welcome" id="welcome"></p>
    </header>

    <section class="mf-stats" id="stats"></section>

    <section class="mf-card mf-controls">
      <div class="mf-row">
        <div class="mf-field grow">
          <label for="q">Search farmers</label>
          <div class="mf-search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons['search'] ?></svg>
            <input type="text" id="q" placeholder="Name, email, phone or barangay" autocomplete="off">
          </div>
        </div>
        <div class="mf-field">
          <label for="town">Municipality</label>
          <select id="town"></select>
        </div>
        <div class="mf-field">
          <label for="crop">Crop</label>
          <select id="crop"></select>
        </div>
        <div class="mf-field">
          <label for="sort">Sort by</label>
          <select id="sort">
            <option value="name">Name (A to Z)</option>
            <option value="new">Newest members</option>
            <option value="size">Largest farm</option>
            <option value="active">Recently active</option>
          </select>
        </div>
      </div>
      <div class="mf-pills" id="status" role="group" aria-label="Filter by account status"></div>
    </section>

    <p class="mf-count" id="count" role="status"></p>

    <section class="mf-card">
      <div class="mf-table-wrap">
        <table class="mf-table">
          <thead>
            <tr><th>Farmer</th><th>Location</th><th>Crops</th><th>Farm size</th><th>Status</th><th>Last active</th><th></th></tr>
          </thead>
          <tbody id="rows"></tbody>
        </table>
      </div>
      <div class="mf-empty" id="empty">No farmers match these filters.</div>
    </section>

    <p class="mf-foot">
      Farmer records are sample data. Edits and account status changes are saved in this browser only until your farm database is connected.
      <button type="button" id="resetEdits">Reset sample edits</button>
    </p>
  </main>
</div>

<div class="mf-overlay" id="overlay"></div>
<aside class="mf-drawer" id="drawer" role="dialog" aria-modal="true" aria-labelledby="dName" tabindex="-1"></aside>

<script type="module" src="../assets/js/auth.js"></script>
<script>window.AGRITRACK_FARMERS = <?= $payload ?>;</script>
<script src="../assets/js/farmers.js"></script>
</body>
</html>