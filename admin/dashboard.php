<?php
// admin/dashboard.php
// All figures come from Firebase (see assets/js/admin-dashboard.js).
// Same sidebar, header and look as the other admin pages (admin/includes + assets/css/admin.css).

$pageTitle = 'Admin dashboard';
$active    = 'dashboard';
$heading   = 'Admin dashboard';
$subtitle  = 'An overview of every farmer, crop and harvest on AgriTrack.';

$extraHead = <<<'HTML'
<!-- DARK MODE (1/4): apply the saved theme BEFORE the CSS paints (prevents a white flash).
     Same script and same "theme" key as the login page, so the choice carries over. -->
<script>
  try {
    var t = localStorage.getItem('theme');
    if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.setAttribute('data-theme', 'dark');
    }
  } catch (e) {}
</script>
<link href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    /* Dashboard only. Every class is prefixed ad- so nothing clashes with admin.css.
       The class names are the ones assets/js/admin-dashboard.js draws, so only the look changed.
       DARK MODE (2/4): every colour is a variable; dark mode only swaps the values. */
    .ad {
        --ink: #1c2a22; --dark: #08301d; --green: #1a8348; --mint: #e6f5d6;
        --line: #dbe6d0; --muted: #56655b; --amber: #d99a00; --red: #d6453d; --blue: #3a86c8;
        --card: #ffffff; --row-line: #eef3e8; --track: #e6f5d6; --zero: #e7f0dc;
        --shadow: rgba(8, 48, 29, .3);
        --ok-bg: #e6f5d6;   --ok-fg: #12663a;
        --off-bg: #edf0ea;  --off-fg: #56655b;
        --soon-bg: #fdf1d8; --soon-fg: #a8710d;
        --warn-bg: #fdecea; --warn-fg: #b8402b;
        --note-bg: #fdf6e0; --note-fg: #7a5a00;
        color: var(--ink); font-variant-numeric: tabular-nums;
    }
    :root[data-theme="dark"] { color-scheme: dark; }
    :root[data-theme="dark"] body { background: #0d1712; color: #e4efe7; }
    :root[data-theme="dark"] .ad {
        --ink: #e4efe7; --dark: #f1f8f3; --green: #4cc27f; --mint: #1d3326;
        --line: #2b3d33; --muted: #9db1a5; --amber: #f0b429; --red: #ef6b63; --blue: #5aa5e0;
        --card: #14211a; --row-line: #22332a; --track: #22352a; --zero: #22352a;
        --shadow: rgba(0, 0, 0, .6);
        --ok-bg: #1d3a2a;   --ok-fg: #7fdca6;
        --off-bg: #25322b;  --off-fg: #a9bbb0;
        --soon-bg: #3b3115; --soon-fg: #f0c862;
        --warn-bg: #3d1f1c; --warn-fg: #f59a8f;
        --note-bg: #3b3115; --note-fg: #f0c862;
    }
    .ad * { box-sizing: border-box; }
    .ad-sample { display: none; margin: 0 0 18px; padding: 8px 14px; border-radius: 999px; background: var(--note-bg); color: var(--note-fg); font-size: .82rem; font-weight: 600; width: fit-content; }

    .ad-card { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 22px 24px; box-shadow: 0 10px 24px -18px var(--shadow); }
    .ad-card h2 { margin: 0 0 2px; font-family: var(--font-display); font-size: 17px; letter-spacing: -.2px; color: var(--dark); }
    .ad-card > p { margin: 0 0 16px; font-size: .84rem; color: var(--muted); }
    .ad-card .ad-link { float: right; font-size: .84rem; font-weight: 700; color: var(--green); text-decoration: none; }
    .ad-card .ad-link:hover { text-decoration: underline; }

    /* Stats */
    .ad-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 16px; }
    .ad-stat { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 18px 20px; box-shadow: 0 10px 24px -18px var(--shadow); }
    .ad-stat small { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--green); }
    .ad-stat b { display: block; margin: 8px 0 2px; font-family: var(--font-display); font-size: 1.8rem; font-weight: 800; color: var(--dark); line-height: 1.1; }
    .ad-stat span { font-size: .82rem; color: var(--muted); }
    .ad-stat.warn b { color: var(--red); }

    /* Layout */
    .ad-grid { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 16px; margin-bottom: 16px; align-items: start; }
    .ad-grid.even { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ad-stack { display: grid; gap: 16px; }

    /* Monthly chart */
    .ad-chart { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; align-items: end; height: 190px; padding-top: 10px; }
    .ad-col { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 6px; }
    .ad-col i { display: block; width: 100%; max-width: 46px; border-radius: 10px 10px 4px 4px; background: linear-gradient(180deg, #8bd63f, #1a8348); min-height: 4px; }
    .ad-col.zero i { background: var(--zero); }
    .ad-col b { font-size: .8rem; color: var(--dark); }
    .ad-col small { font-size: .74rem; color: var(--muted); }

    /* Horizontal bars */
    .ad-bars { display: grid; gap: 12px; }
    .ad-bar-top { display: flex; justify-content: space-between; gap: 10px; font-size: .86rem; margin-bottom: 5px; }
    .ad-bar-top b { color: var(--dark); }
    .ad-bar-top span { color: var(--muted); }
    .ad-track { height: 10px; border-radius: 99px; background: var(--track); overflow: hidden; }
    .ad-track i { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, #2fa55f, #8bd63f); }
    .ad-track.blue i { background: var(--blue); }

    /* Lists */
    .ad-list { display: grid; gap: 0; }
    .ad-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 0; border-top: 1px solid var(--row-line); }
    .ad-row:first-child { border-top: 0; padding-top: 2px; }
    .ad-row b { display: block; font-size: .92rem; color: var(--dark); }
    .ad-row small { display: block; font-size: .8rem; color: var(--muted); margin-top: 2px; line-height: 1.4; }
    .ad-row > span { font-size: .78rem; color: var(--muted); white-space: nowrap; }
    .ad-pill { font-size: .74rem; font-weight: 700; padding: 4px 11px; border-radius: 999px; white-space: nowrap; }
    .ad-pill.active, .ad-pill.ok { background: var(--ok-bg); color: var(--ok-fg); }
    .ad-pill.inactive, .ad-pill.done { background: var(--off-bg); color: var(--off-fg); }
    .ad-pill.soon { background: var(--soon-bg); color: var(--soon-fg); }
    .ad-pill.warn { background: var(--warn-bg); color: var(--warn-fg); }
    .ad-none { margin: 0; padding: 18px 0 6px; text-align: center; color: var(--muted); font-size: .9rem; }
    .ad-card > p.ad-none { margin: 0; font-size: .9rem; }

    .ad-map { height: 360px; border-radius: 14px; border: 1px solid var(--line); z-index: 0; }
    .ad-pin b { color: var(--dark); }
    .ad-empty { display: none; margin-bottom: 16px; padding: 36px 24px; text-align: center; color: var(--muted); background: var(--card); border: 1px dashed var(--line); border-radius: 18px; }
    .ad-empty b { display: block; color: var(--dark); font-size: 1.1rem; margin-bottom: 6px; }
    .ad-empty a { color: var(--green); font-weight: 700; }

    /* Dark mode for the Leaflet map, popups and zoom buttons */
    :root[data-theme="dark"] .ad-map .leaflet-tile-pane { filter: invert(1) hue-rotate(180deg) brightness(.95) contrast(.9); }
    :root[data-theme="dark"] .ad-map .leaflet-popup-content-wrapper,
    :root[data-theme="dark"] .ad-map .leaflet-popup-tip { background: #14211a; color: #e4efe7; }
    :root[data-theme="dark"] .ad-map .leaflet-control-zoom a { background: #14211a; color: #e4efe7; border-color: #2b3d33; }

    #theme-toggle.ad-theme-fallback { position: fixed; right: 16px; bottom: 16px; z-index: 1000; width: 46px; height: 46px; display: grid; place-items: center; border-radius: 50%; border: 1px solid #dbe6d0; background: #fff; color: #08301d; font-size: 20px; cursor: pointer; box-shadow: 0 8px 20px -10px rgba(8, 48, 29, .45); }
    #theme-toggle.ad-theme-fallback:hover { border-color: #1a8348; }
    #theme-toggle.ad-theme-fallback:focus-visible { outline: 3px solid #1a8348; outline-offset: 2px; }
    :root[data-theme="dark"] #theme-toggle.ad-theme-fallback { background: #14211a; color: #e4efe7; border-color: #2b3d33; }

    @media (max-width: 1100px) {
        .ad-stats { grid-template-columns: repeat(2, 1fr); }
        .ad-grid, .ad-grid.even { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
        .ad-stats { gap: 10px; }
        .ad-card { padding: 18px; }
    }
</style>
HTML;

require __DIR__ . '/includes/header.php';
?>

        <div class="ad">

            <span class="ad-sample" id="sampleNote">Some of these figures come from sample farmers. Remove them in Manage farmers when you are done.</span>

            <div class="ad-empty" id="empty">
                <b>No farmers yet</b>
                Farmers show up here once they register. To try the dashboard first, open
                <a href="manage-farmers.php">Manage farmers</a> and choose Load 12 sample farmers.
            </div>

            <section class="ad-stats" id="stats"></section>

            <div class="ad-grid">
                <section class="ad-card">
                    <h2>Harvested per month (tons)</h2>
                    <p>Last six months, all farmers combined.</p>
                    <div class="ad-chart" id="chart"></div>
                </section>
                <section class="ad-card">
                    <a class="ad-link" href="manage-farmers.php">Manage &rarr;</a>
                    <h2>Recently joined</h2>
                    <p>The newest farmers.</p>
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

            <section class="ad-card" style="margin-bottom:16px">
                <h2>Farmer locations</h2>
                <p>Each pin is a farm. Pins without a GPS fix sit near the town centre.</p>
                <div class="ad-map" id="farmMap" role="region" aria-label="Map of farmer locations"></div>
                <p class="ad-none" id="mapNone" style="display:none">No farmer has set a location yet. Farmers are asked for it the next time they sign in.</p>
            </section>

            <div class="ad-grid even">
                <section class="ad-card">
                    <h2>Needs attention</h2>
                    <p>Crops that are dry or almost ready to harvest.</p>
                    <div class="ad-list" id="attention"></div>
                </section>
                <section class="ad-card">
                    <h2>Recent activity</h2>
                    <p>The latest actions by farmers.</p>
                    <div class="ad-list" id="activity"></div>
                </section>
            </div>

        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
(function () {
    try {
        if (document.getElementById('theme-toggle')) return;
        var b = document.createElement('button');
        b.id = 'theme-toggle';
        b.type = 'button';
        b.className = 'theme-toggle ad-theme-fallback';
        b.setAttribute('aria-label', 'Toggle dark mode');
        b.setAttribute('aria-pressed', 'false');
        b.innerHTML = '<i class="ph-bold ph-moon theme-icon" aria-hidden="true"></i>';
        document.body.appendChild(b);
    } catch (e) {}
})();
</script>

<script src="../assets/js/theme.js"></script>
<script type="module" src="../assets/js/auth.js"></script>
<script type="module" src="../assets/js/admin-dashboard.js"></script>
<script src="../assets/js/chatbot.js"></script>
</body>
</html>