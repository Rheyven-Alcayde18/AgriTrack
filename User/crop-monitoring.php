<?php
// user/crop-monitoring.php
// No sample data here. Crops come from Firebase (see assets/js/crops.js).
// Same sidebar and look as the admin pages (user/includes + assets/css/admin.css).
// The IDs and class names below are the ones crops.js draws into, so only the look changed.

$pageTitle  = 'Crop monitoring';
$active     = 'crop-monitoring';
$heading    = 'Crop monitoring';
$subtitle   = 'Track growth stage, soil moisture and harvest dates for every field.';
$topActions = '<button type="button" class="btn primary" id="addCrop"><i class="ph-bold ph-plus" aria-hidden="true"></i> Add crop</button>';

$extraHead = <<<'HTML'
<style>
    /* Crop monitoring page only. Classes are prefixed cm- (page) and ag- (buttons, dialogs, empty state) */
    .cm {
        --ink: var(--ink-900); --dark: var(--forest-900); --green: var(--leaf-600);
        --mint: var(--lime-100); --muted: var(--ink-600);
        color: var(--ink); font-variant-numeric: tabular-nums;
    }
    .cm * { box-sizing: border-box; }

    /* Summary (hidden while there are no crops) */
    .cm-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .cm:has(.ag-empty) .cm-stats { display: none; }
    .cm-stat { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 18px 20px; box-shadow: 0 10px 24px -18px rgba(8, 48, 29, .3); }
    .cm-stat small { display: block; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--green); }
    .cm-stat b { display: block; margin: 8px 0 2px; font-family: var(--font-display); font-size: 1.8rem; font-weight: 800; color: var(--dark); line-height: 1.1; }
    .cm-stat span { font-size: .82rem; color: var(--muted); }
    .cm-stat.warn b { color: var(--danger); }

    /* Bar + filters */
    .cm-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
    .cm-title { display: flex; align-items: center; gap: 14px; }
    .cm-title h2 { margin: 0; font-family: var(--font-display); font-size: 19px; letter-spacing: -.3px; color: var(--dark); }
    .cm-filters { display: flex; flex-wrap: wrap; gap: 8px; }
    .cm:has(.ag-empty) .cm-filters { display: none; }
    .cm-filter {
        font: inherit; font-size: .86rem; font-weight: 700; cursor: pointer; padding: 8px 16px; border-radius: 999px;
        background: #fff; color: var(--muted); border: 1px solid var(--line); transition: all .15s;
    }
    .cm-filter:hover { color: var(--dark); border-color: var(--green); }
    .cm-filter:focus-visible { outline: 3px solid rgba(47, 165, 95, .4); outline-offset: 2px; }
    .cm-filter.active { color: #fff; border-color: transparent; background: linear-gradient(180deg, #35b06a, var(--leaf-700)); }

    /* Buttons used inside the generated cards and dialogs */
    .ag-btn {
        font: inherit; font-size: .88rem; font-weight: 700; cursor: pointer; padding: 10px 18px; border-radius: 999px; border: 0;
        color: #fff; background: linear-gradient(180deg, #35b06a, var(--leaf-700)); transition: filter .15s, transform .15s;
    }
    .ag-btn:hover { filter: brightness(1.08); transform: translateY(-1px); }
    .ag-btn:focus-visible, .ag-ghost:focus-visible { outline: 3px solid rgba(47, 165, 95, .4); outline-offset: 2px; }
    .ag-btn:disabled { opacity: .6; cursor: default; transform: none; }
    .ag-ghost { font: inherit; font-size: .82rem; font-weight: 700; cursor: pointer; padding: 8px 14px; border-radius: 999px; border: 1px solid var(--line); background: #fff; color: var(--forest-800); transition: all .15s; }
    .ag-ghost:hover { border-color: var(--green); color: var(--green); }
    .ag-ghost.danger { color: var(--danger); }
    .ag-ghost.danger:hover { border-color: var(--danger); color: var(--danger); }
    .ag-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }

    /* Empty state: no crops added yet */
    .ag-empty { padding: 56px 24px; text-align: center; color: var(--muted); background: #fff; border: 1.5px dashed #c9dbbc; border-radius: 22px; }
    .ag-empty b { display: block; margin-bottom: 6px; font-family: var(--font-display); font-size: 1.3rem; color: var(--dark); }
    .ag-empty p { margin: 0 auto; max-width: 420px; line-height: 1.55; }
    .ag-empty .ag-actions { justify-content: center; }
    .ag-empty [data-act="demo"] { display: none; }

    /* Crop cards: a colored top edge shows the status at a glance */
    .cm-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
    .cm-card {
        --accent: var(--leaf-500);
        background: #fff; border: 1px solid var(--line); border-top: 4px solid var(--accent); border-radius: 20px; padding: 24px;
        box-shadow: 0 12px 28px -20px rgba(8, 48, 29, .35);
        transition: box-shadow .2s, transform .2s; scroll-margin-top: 16px;
    }
    .cm-card[data-status="soon"] { --accent: #e0a000; }
    .cm-card[data-status="warn"] { --accent: #d6453d; }
    .cm-card[data-status="done"] { --accent: #9bb0a5; }
    .cm-card:hover { box-shadow: 0 18px 36px -18px rgba(8, 48, 29, .4); transform: translateY(-2px); }
    .cm-card[hidden] { display: none; }
    .cm-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
    .cm-name { display: flex; align-items: center; gap: 12px; }
    .cm-badge {
        flex: none; width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center;
        background: linear-gradient(145deg, #f0f9e4, var(--mint)); box-shadow: inset 0 0 0 1px rgba(26, 131, 72, .14);
    }
    .cm-badge svg { width: 22px; height: 22px; fill: none; stroke: var(--green); stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .cm-name b { display: block; font-family: var(--font-display); font-size: 1.2rem; letter-spacing: -.3px; color: var(--dark); }
    .cm-name small { color: var(--muted); font-size: .84rem; }
    .cm-pill { font-size: .78rem; font-weight: 700; padding: 6px 12px; border-radius: 999px; white-space: nowrap; }
    .cm-pill.ok   { background: var(--mint); color: var(--leaf-700); }
    .cm-pill.soon { background: #fdf1d8; color: #a8710d; }
    .cm-pill.warn { background: #fdecea; color: var(--danger); }
    .cm-pill.done { background: #edf0ea; color: var(--ink-600); }

    .cm-steps { display: grid; grid-template-columns: repeat(5, 1fr); margin: 22px 0 8px; }
    .cm-step { position: relative; text-align: center; font-size: .7rem; font-weight: 600; color: #a6bcb0; padding-top: 22px; }
    .cm-step::before { content: ""; position: absolute; top: 5px; left: 50%; width: 11px; height: 11px; margin-left: -5.5px; border-radius: 50%; background: #dbe6d0; z-index: 1; }
    .cm-step::after { content: ""; position: absolute; top: 10px; left: -50%; width: 100%; height: 2px; background: #dbe6d0; }
    .cm-step:first-child::after { display: none; }
    .cm-step.done { color: var(--muted); }
    .cm-step.done::before, .cm-step.done::after { background: var(--green); }
    .cm-step.now { color: var(--dark); font-weight: 800; }
    .cm-step.now::before { background: var(--green); box-shadow: 0 0 0 5px rgba(47, 165, 95, .2); }
    .cm-step.now::after { background: var(--green); }

    .cm-prog { display: flex; align-items: center; gap: 12px; margin: 14px 0 20px; }
    .cm-bar-track { flex: 1; height: 10px; border-radius: 99px; background: #e6f0dc; overflow: hidden; }
    .cm-bar-track i { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--leaf-500), var(--grass-500)); }
    .cm-prog b { font-size: .9rem; color: var(--dark); min-width: 38px; text-align: right; }

    .cm-facts { display: grid; grid-template-columns: repeat(3, 1fr); border-top: 1px solid #eef3e8; }
    .cm-fact { padding: 16px 14px 4px 0; }
    .cm-fact + .cm-fact { padding-left: 16px; border-left: 1px solid #eef3e8; }
    .cm-fact small { display: block; font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
    .cm-fact b { display: block; margin-top: 6px; font-family: var(--font-display); font-size: 1.1rem; color: var(--dark); }
    .cm-fact span { display: block; margin-top: 2px; font-size: .78rem; color: var(--muted); }
    .cm-moist { height: 6px; border-radius: 99px; background: #e0ecf5; margin-top: 8px; overflow: hidden; }
    .cm-moist i { display: block; height: 100%; border-radius: 99px; background: #3a86c8; }
    .cm-moist.low i { background: var(--danger); }

    .cm-action { margin: 18px 0 0; padding: 12px 16px; border-radius: 12px; background: var(--mint); font-size: .88rem; color: #35584a; line-height: 1.5; }
    .cm-action b { color: var(--dark); }
    .cm-card[data-status="warn"] .cm-action { background: #fdeeec; }
    .cm-card[data-status="soon"] .cm-action { background: #fdf6e0; }

    .cm-empty { display: none; padding: 40px; text-align: center; color: var(--muted); background: #fff; border: 1.5px dashed #c9dbbc; border-radius: 20px; }

    /* Dialogs */
    .cm-dlg { border: 0; border-radius: 22px; padding: 0; width: min(540px, calc(100vw - 32px)); color: var(--ink); font-family: inherit; box-shadow: 0 30px 70px rgba(8, 48, 29, .35); }
    .cm-dlg::backdrop { background: rgba(8, 30, 20, .5); backdrop-filter: blur(2px); }
    .cm-dlg form { padding: 26px; display: grid; gap: 14px; }
    .cm-dlg h3 { margin: 0; font-family: var(--font-display); font-size: 1.3rem; letter-spacing: -.3px; color: var(--dark); }
    .cm-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .cm-dlg label { display: flex; flex-direction: column; gap: 6px; font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
    .cm-dlg input { font: inherit; font-size: .95rem; font-weight: 500; letter-spacing: 0; text-transform: none; padding: 11px 14px; border: 1.5px solid var(--line); border-radius: 12px; color: var(--ink); background: #fff; outline: none; width: 100%; transition: border-color .2s, box-shadow .2s; }
    .cm-dlg input:focus { border-color: var(--green); box-shadow: 0 0 0 4px rgba(47, 165, 95, .16); }
    .cm-err { margin: 0; min-height: 1.1em; font-size: .84rem; font-weight: 600; color: var(--danger); }
    .cm-dlg .ag-actions { justify-content: flex-end; margin-top: 0; }

    .ag-toast { position: fixed; left: 50%; bottom: 28px; transform: translateX(-50%); z-index: 50; background: var(--forest-800); color: #fff; padding: 12px 20px; border-radius: 999px; font-size: .88rem; font-weight: 600; box-shadow: 0 10px 30px rgba(0, 0, 0, .25); }

    @media (max-width: 1100px) {
        .cm-stats { grid-template-columns: repeat(2, 1fr); }
        .cm-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
        .cm-stats { grid-template-columns: 1fr 1fr; gap: 10px; }
        .cm-card { padding: 18px; }
        .cm-facts { grid-template-columns: 1fr; }
        .cm-fact + .cm-fact { padding-left: 0; border-left: 0; border-top: 1px solid #eef3e8; margin-top: 12px; }
        .cm-step { font-size: .62rem; }
        .cm-grid2 { grid-template-columns: 1fr; }
    }
</style>
HTML;

require __DIR__ . '/includes/header.php';
?>

        <div class="cm">

            <section class="cm-stats" id="stats"></section>

            <div class="cm-bar">
                <div class="cm-title">
                    <h2>Your crops</h2>
                </div>
                <div class="cm-filters" id="filters" role="group" aria-label="Filter crops">
                    <button type="button" class="cm-filter active" data-filter="all">All</button>
                    <button type="button" class="cm-filter" data-filter="ok">On track</button>
                    <button type="button" class="cm-filter" data-filter="soon">Harvest soon</button>
                    <button type="button" class="cm-filter" data-filter="warn">Needs attention</button>
                </div>
            </div>

            <section class="cm-grid" id="cropGrid"></section>
            <div class="cm-empty" id="empty">No crops match this filter.</div>

        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<!-- Add / edit crop -->
<dialog class="cm-dlg" id="cropDlg">
    <form id="cropForm" novalidate>
        <h3 id="cropTitle">Add crop</h3>
        <div class="cm-grid2">
            <label>Crop <input name="name" type="text" list="cropNames" placeholder="e.g. Rice" autocomplete="off"></label>
            <label>Field <input name="field" type="text" placeholder="e.g. Field A" autocomplete="off"></label>
            <label>Area (ha) <input name="area" type="number" min="0" step="0.1"></label>
            <label>Soil moisture (%) <input name="moisture" type="number" min="0" max="100" step="1" value="60"></label>
            <label>Planted <input name="planted" type="date"></label>
            <label>Harvest date <input name="harvest" type="date"></label>
            <label>Expected yield (t) <input name="yield_t" type="number" min="0" step="0.1"></label>
            <label>Price (&#8369;/kg) <input name="price" type="number" min="0" step="0.5"></label>
        </div>
        <datalist id="cropNames"><option value="Rice"><option value="Corn"><option value="Tomato"><option value="Eggplant"></datalist>
        <p class="cm-err" id="cropErr" role="alert"></p>
        <div class="ag-actions">
            <button type="button" class="ag-ghost" data-close>Cancel</button>
            <button type="submit" class="ag-btn" id="cropSave">Save crop</button>
        </div>
    </form>
</dialog>

<!-- Log soil moisture -->
<dialog class="cm-dlg" id="moistDlg">
    <form id="moistForm" novalidate>
        <h3 id="moistTitle">Log soil moisture</h3>
        <label>Soil moisture (%) <input name="moisture" type="number" min="0" max="100" step="1"></label>
        <p class="cm-err" id="moistErr" role="alert"></p>
        <div class="ag-actions">
            <button type="button" class="ag-ghost" data-close>Cancel</button>
            <button type="submit" class="ag-btn">Save reading</button>
        </div>
    </form>
</dialog>

<!-- Record harvest -->
<dialog class="cm-dlg" id="harvDlg">
    <form id="harvForm" novalidate>
        <h3 id="harvTitle">Record harvest</h3>
        <div class="cm-grid2">
            <label>Date <input name="date" type="date"></label>
            <label>Yield (t) <input name="yield_t" type="number" min="0" step="0.1"></label>
            <label>Price (&#8369;/kg) <input name="price" type="number" min="0" step="0.5"></label>
        </div>
        <p class="cm-err" id="harvErr" role="alert"></p>
        <div class="ag-actions">
            <button type="button" class="ag-ghost" data-close>Cancel</button>
            <button type="submit" class="ag-btn">Save harvest</button>
        </div>
    </form>
</dialog>

<script type="module" src="../assets/js/auth.js"></script>
<script type="module" src="../assets/js/crops.js"></script>
</body>
</html>