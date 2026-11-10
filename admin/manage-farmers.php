<?php
$icons = [
  'home'     => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
  'farmers'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17.5" cy="9" r="2.5"/><path d="M17 14.2c2.6.2 4.5 2.2 4.5 5.3"/>',
  'weather'  => '<path d="M17.5 19a4.5 4.5 0 1 0-1-8.9A6 6 0 0 0 4.7 12 3.5 3.5 0 0 0 6.5 19Z"/>',
  'reports'  => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/>',
  'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
  'leaf'     => '<path d="M6 18c0-8 5-13 13-13 0 8-5 13-13 13Z"/><path d="M6 18 14 10"/>',
];
function ic($k) { global $icons; return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[$k] . '</svg>'; }

$nav = [
  ['dashboard.php', 'Dashboard', 'home'],
  ['manage-farmers.php', 'Manage farmers', 'farmers'],
  ['weather-forecast.php', 'Weather forecast', 'weather'],
  ['reports.php', 'Reports', 'reports'],
  ['settings.php', 'Settings', 'settings'],
];
$current = 'manage-farmers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AgriTrack | Manage Farmers</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <style>
    .ad { --ink: #12382a; --dark: #0c4a30; --green: #1a9d5c; --mint: #eef7f1; --line: #e1eee6; --muted: #6b8a7a; --red: #d6453d; }
    .ad-card { background: #fff; border: 1px solid var(--line); border-radius: 20px; padding: 22px 24px; }
    .ad-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 10px; font-size: .84rem; font-weight: 700; border: none; cursor: pointer; text-decoration: none; }
    .ad-btn-primary { background: var(--dark); color: #fff; }
    .ad-btn-outline { background: #fff; border: 1px solid var(--line); color: var(--dark); }
    .ad-btn-danger { background: #fde6e3; color: var(--red); }
    
    .ad-table { width: 100%; border-collapse: collapse; margin-top: 16px; text-align: left; }
    .ad-table th { padding: 12px; font-size: .75rem; text-transform: uppercase; color: var(--muted); border-bottom: 2px solid var(--line); }
    .ad-table td { padding: 14px 12px; font-size: .88rem; border-bottom: 1px solid var(--line); }
    .ad-pill { font-size: .74rem; font-weight: 700; padding: 4px 10px; border-radius: 999px; }
    .ad-pill.active { background: #e3f4ea; color: #15753f; }
    .ad-pill.inactive { background: #edf1ef; color: #5b756a; }
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
    <div style="display:flex; justify-between; align-items:center; margin-bottom: 22px;">
      <div>
        <h1 style="margin:0;">Manage Farmers</h1>
        <p style="margin:4px 0 0; color: var(--muted);">View and manage registered farmers across all municipalities.</p>
      </div>
      <div style="display:flex; gap:10px;">
        <button id="loadSample" class="ad-btn ad-btn-outline">Load Sample Data</button>
        <button id="clearSample" class="ad-btn ad-btn-danger">Clear Sample Data</button>
      </div>
    </div>

    <section class="ad-card">
      <table class="ad-table">
        <thead>
          <tr>
            <th>Farmer Name</th>
            <th>Municipality</th>
            <th>Farm Size</th>
            <th>Active Crops</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="farmersTableBody">
          <tr><td colspan="6" style="text-align:center; padding: 20px; color: var(--muted);">Loading registered farmers...</td></tr>
        </tbody>
      </table>
    </section>
  </main>
</div>

<script type="module" src="../assets/js/auth.js"></script>
<script type="module" src="../assets/js/manage-farmers.js"></script>
</body>
</html>