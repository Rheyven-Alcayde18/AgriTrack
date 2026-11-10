<?php
// admin/manage-farmers.php
// Admin page: see every farmer account, search and filter them, view their crops and farm location,
// change a farm location or size, and activate or deactivate accounts.
// Same sidebar, header and look as the other admin pages (admin/includes + assets/css/admin.css).
// Data comes from Firebase through assets/js/db.js. Only role "admin" can stay (db.js redirects everyone else).

$pageTitle = 'Manage farmers';
$active    = 'manage-farmers';
$heading   = 'Manage farmers';
$subtitle  = 'View every farmer account, check their farms and crops, and switch accounts on or off.';

$topActions = <<<'HTML'
<button type="button" class="mf-btn" id="refreshBtn">
    <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 11a8 8 0 0 0-14.5-4M4 4v4h4"/><path d="M4 13a8 8 0 0 0 14.5 4M20 20v-4h-4"/></svg>
    Refresh
</button>
HTML;

$extraHead = <<<'HTML'
<style>
    /* Manage-farmers page only. Every class is prefixed mf- so nothing clashes with admin.css */
    .mf {
      --ink: #12382a;
      --dark: #0c4a30;
      --green: #1a9d5c;
      --mint: #eef7f1;
      --line: #e1eee6;
      --muted: #6b8a7a;
      --danger: #b8402b;
      color: var(--ink);
    }
    .mf * { box-sizing: border-box; }
    .mf-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 26px; }
    .mf-head h1 { margin: 0 0 4px; }
    .mf-head p { margin: 0; color: var(--muted); }
    .mf-head .mf-welcome:empty { display: none; }

    .mf-btn {
      display: inline-flex; align-items: center; gap: 7px; font: inherit; font-weight: 700; font-size: .88rem; cursor: pointer;
      padding: 9px 16px; border-radius: 12px; border: 1px solid var(--line); background: #fff; color: var(--dark);
      transition: border-color .15s, background .15s, color .15s;
    }
    .mf-btn:hover { border-color: var(--green); color: var(--green); }
    .mf-btn:focus-visible { outline: 3px solid rgba(26,157,92,.35); outline-offset: 2px; }
    .mf-btn svg { flex: none; }
    .mf-btn.small { padding: 6px 12px; font-size: .8rem; border-radius: 10px; }
    .mf-btn.primary { background: var(--dark); border-color: var(--dark); color: #fff; }
    .mf-btn.primary:hover { background: var(--green); border-color: var(--green); color: #fff; }
    .mf-btn.primary.danger-solid { background: var(--danger); border-color: var(--danger); }
    .mf-btn.danger { color: var(--danger); }
    .mf-btn.danger:hover { border-color: var(--danger); color: var(--danger); }
    .mf-btn[disabled] { opacity: .55; cursor: default; }

    .mf-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; }
    .mf-stat { display: flex; align-items: center; gap: 14px; padding: 16px 18px; background: #fff; border: 1px solid var(--line); border-radius: 16px; }
    .mf-stat > i { flex: none; width: 44px; height: 44px; border-radius: 12px; background: var(--mint); display: grid; place-items: center; }
    .mf-stat > i svg { width: 22px; height: 22px; fill: none; stroke: var(--green); stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }
    .mf-stat b { display: block; font-size: 1.55rem; line-height: 1.1; color: var(--dark); }
    .mf-stat span { display: block; margin-top: 3px; font-size: .82rem; font-weight: 600; color: var(--muted); }

    .mf-toolbar { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; }
    .mf-search {
      position: relative; flex: 1 1 260px; display: flex; align-items: center;
      background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 4px 6px 4px 14px; transition: border-color .15s, box-shadow .15s;
    }
    .mf-search:focus-within { border-color: var(--green); box-shadow: 0 0 0 4px rgba(26,157,92,.12); }
    .mf-search svg { width: 18px; height: 18px; flex: none; fill: none; stroke: var(--muted); stroke-width: 2; stroke-linecap: round; }
    .mf-search input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; font: inherit; padding: 10px; color: var(--ink); }
    .mf-select {
      font: inherit; min-width: 170px; padding: 11px 14px; color: var(--ink); cursor: pointer;
      background: #fff; border: 1px solid var(--line); border-radius: 14px; transition: border-color .15s, box-shadow .15s;
    }
    .mf-select:focus { outline: 0; border-color: var(--green); box-shadow: 0 0 0 4px rgba(26,157,92,.12); }

    .mf-card { background: #fff; border: 1px solid var(--line); border-radius: 18px; overflow: hidden; }
    .mf-table-wrap { overflow-x: auto; }
    .mf table { width: 100%; border-collapse: collapse; table-layout: auto; }
    .mf th { padding: 12px 10px; text-align: left; white-space: nowrap; font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); background: var(--mint); border-bottom: 1px solid var(--line); }
    .mf td { padding: 12px 10px; font-size: .88rem; border-bottom: 1px solid #eef5f0; vertical-align: middle; }
    .mf tbody tr:last-child td { border-bottom: 0; }
    .mf tbody tr:hover { background: #f8fcf9; }
    .mf .mf-num { text-align: center; }
    .mf td.mf-actions { white-space: nowrap; text-align: right; }
    .mf td.mf-nowrap { white-space: nowrap; }
    .mf .mf-btn.icon { padding: 6px 9px; }
    .mf td.mf-actions .mf-btn + .mf-btn { margin-left: 6px; }
    .mf-person { display: flex; align-items: center; gap: 10px; }
    .mf-person > div:last-child { min-width: 0; overflow-wrap: anywhere; }
    .mf-avatar { flex: none; width: 38px; height: 38px; display: grid; place-items: center; font-size: .85rem; font-weight: 800; color: #fff; border-radius: 50%; background: linear-gradient(145deg, #34b974, var(--dark)); }
    .mf-person b { display: block; color: var(--dark); }
    .mf-person small { display: block; margin-top: 1px; font-size: .78rem; color: var(--muted); }
    .mf-muted { color: var(--muted); }
    .mf-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 999px; font-size: .76rem; font-weight: 700; }
    .mf-pill::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .mf-pill.active { color: var(--green); background: var(--mint); }
    .mf-pill.inactive { color: var(--danger); background: #fdecea; }
    .mf-state { padding: 50px 24px; text-align: center; color: var(--muted); }
    .mf-state b { display: block; margin-bottom: 4px; font-size: 1rem; color: var(--dark); }
    .mf-ico { display: grid; place-items: center; margin: 0 auto 10px; width: 46px; height: 46px; border-radius: 14px; background: var(--mint); color: var(--green); }
    .mf-ico svg { width: 24px; height: 24px; }
    .mf-foot { padding: 12px 16px; font-size: .82rem; color: var(--muted); background: #fafdfb; border-top: 1px solid var(--line); }

    /* Dialogs */
    .mf dialog { width: min(560px, calc(100vw - 32px)); padding: 0; border: 0; border-radius: 20px; color: var(--ink); box-shadow: 0 30px 70px rgba(8, 48, 29, .35); }
    .mf dialog::backdrop { background: rgba(8, 30, 20, .5); }
    .mf-dlg { padding: 26px; }
    .mf-dlg h2 { margin: 0; font-size: 1.25rem; color: var(--dark); }
    .mf-dlg p.sub { margin: 4px 0 0; font-size: .9rem; color: var(--muted); line-height: 1.55; }
    .mf-dlg-head { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
    .mf-dlg-head .mf-avatar { width: 50px; height: 50px; font-size: 1rem; }
    .mf-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; margin: 0 0 18px; }
    .mf-grid2 dt { font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
    .mf-grid2 dd { margin: 2px 0 0; font-size: .92rem; font-weight: 600; }
    .mf-dlg h3 { margin: 0 0 8px; font-size: .9rem; color: var(--dark); }
    .mf-croplist { list-style: none; margin: 0; padding: 0; max-height: 190px; overflow-y: auto; border: 1px solid var(--line); border-radius: 14px; }
    .mf-croplist li { display: flex; justify-content: space-between; gap: 12px; padding: 10px 14px; font-size: .86rem; border-bottom: 1px solid #eef5f0; }
    .mf-croplist li:last-child { border-bottom: 0; }
    .mf-croplist span { color: var(--muted); text-align: right; }
    .mf-dlg-foot { display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px; }
    .mf-toast { position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%); z-index: 99; padding: 11px 20px; border-radius: 999px; font-size: .88rem; font-weight: 600; color: #fff; background: #0c4a30; box-shadow: 0 12px 30px rgba(0,0,0,.25); }

    .mf-field { display: grid; gap: 6px; margin-top: 16px; }
    .mf-field label { font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
    .mf-field .mf-select { width: 100%; }
    .mf-input { font: inherit; padding: 11px 14px; color: var(--ink); background: #fff; border: 1px solid var(--line); border-radius: 14px; transition: border-color .15s, box-shadow .15s; }
    .mf-input:focus { outline: 0; border-color: var(--green); box-shadow: 0 0 0 4px rgba(26,157,92,.12); }
    .mf-note { margin: 14px 0 0; font-size: .82rem; color: var(--muted); line-height: 1.5; }
    @media (max-width: 1100px) { .mf-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 520px) { .mf-grid2 { grid-template-columns: 1fr; } .mf-select { flex: 1 1 140px; } }
</style>
HTML;

require __DIR__ . '/includes/header.php';

$ico = [
  'farmers' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17.5" cy="9" r="2.5"/><path d="M17 14.2c2.6.2 4.5 2.2 4.5 5.3"/>',
  'check'   => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="m16 11 2 2 4-4"/>',
  'off'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 11h6"/>',
  'plant'   => '<path d="M12 21v-9"/><path d="M12 12c0-4 3-6.5 7-6.5 0 4-3 6.5-7 6.5Z"/><path d="M12 15c0-3-2.3-5-5.5-5 0 3 2.3 5 5.5 5Z"/>',
  'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
];
function mfIc($k) { global $ico; return '<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">' . $ico[$k] . '</svg>'; }
?>

        <div class="mf" id="mfRoot">

    <section class="mf-stats" aria-label="Summary">
      <div class="mf-stat"><i><?= mfIc('farmers') ?></i><div><b id="statTotal">0</b><span>Total farmers</span></div></div>
      <div class="mf-stat"><i><?= mfIc('check') ?></i><div><b id="statActive">0</b><span>Active</span></div></div>
      <div class="mf-stat"><i><?= mfIc('off') ?></i><div><b id="statInactive">0</b><span>Deactivated</span></div></div>
      <div class="mf-stat"><i><?= mfIc('plant') ?></i><div><b id="statCrops">0</b><span>Registered crops</span></div></div>
    </section>

    <div class="mf-toolbar">
      <div class="mf-search">
        <svg viewBox="0 0 24 24" aria-hidden="true"><?= $ico['search'] ?></svg>
        <input type="search" id="searchInput" placeholder="Search by name, email or location" aria-label="Search farmers" autocomplete="off">
      </div>
      <select class="mf-select" id="statusFilter" aria-label="Filter by status">
        <option value="all">All statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Deactivated</option>
      </select>
      <select class="mf-select" id="townFilter" aria-label="Filter by municipality">
        <option value="all">All municipalities</option>
      </select>
    </div>

    <div class="mf-card">
      <div class="mf-table-wrap">
        <table>
          <thead>
            <tr>
              <th>Farmer</th>
              <th>Farm location</th>
              <th class="mf-num">Farm size</th>
              <th class="mf-num">Crops</th>
              <th>Joined</th>
              <th>Last active</th>
              <th>Status</th>
              <th style="text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody id="farmerRows">
            <tr><td colspan="8"><div class="mf-state"><b>Loading farmers...</b>Reading accounts from Firebase.</div></td></tr>
          </tbody>
        </table>
      </div>
      <div class="mf-foot" id="tableFoot" aria-live="polite"></div>
    </div>

    <!-- FARMER DETAILS -->
    <dialog id="detailsDlg" aria-labelledby="detailsName">
      <div class="mf-dlg">
        <div class="mf-dlg-head">
          <div class="mf-avatar" id="detailsAvatar"></div>
          <div>
            <h2 id="detailsName"></h2>
            <p class="sub" id="detailsEmail"></p>
          </div>
        </div>
        <dl class="mf-grid2" id="detailsGrid"></dl>
        <h3>Crops</h3>
        <ul class="mf-croplist" id="detailsCrops"></ul>
        <div class="mf-dlg-foot">
          <button type="button" class="mf-btn" data-close>Close</button>
        </div>
      </div>
    </dialog>

    <!-- EDIT FARM LOCATION -->
    <dialog id="locDlg" aria-labelledby="locTitle">
      <form class="mf-dlg" id="locForm" method="dialog">
        <h2 id="locTitle">Change farm location and size</h2>
        <p class="sub" id="locSub"></p>
        <div class="mf-field">
          <label for="locTown">Municipality</label>
          <select id="locTown" class="mf-select" required></select>
        </div>
        <div class="mf-field">
          <label for="locBrgy">Barangay</label>
          <input type="text" id="locBrgy" class="mf-input" maxlength="60" placeholder="e.g. Dayap" autocomplete="off">
        </div>
        <div class="mf-field">
          <label for="locSize">Farm size (hectares, optional)</label>
          <input type="number" id="locSize" class="mf-input" min="0" step="0.1" placeholder="e.g. 3.5">
        </div>
        <p class="mf-note">The farmer's weather, markets and recommendations will follow this new location the next time they open the app.</p>
        <div class="mf-dlg-foot">
          <button type="button" class="mf-btn" id="locCancel">Cancel</button>
          <button type="submit" class="mf-btn primary" id="locSave">Save location</button>
        </div>
      </form>
    </dialog>

    <!-- CONFIRM -->
    <dialog id="confirmDlg" aria-labelledby="confirmTitle">
      <div class="mf-dlg">
        <h2 id="confirmTitle"></h2>
        <p class="sub" id="confirmText"></p>
        <div class="mf-dlg-foot">
          <button type="button" class="mf-btn" id="confirmCancel">Cancel</button>
          <button type="button" class="mf-btn primary" id="confirmOk">Confirm</button>
        </div>
      </div>
    </dialog>
  
        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<script type="module" src="../assets/js/auth.js"></script>
<script type="module">

    import {
        requireUser, listAllUsers, listAll, updateUser,
        esc, fmtFull, dayDiff, todayISO, signOutAndGo, explain, showError, TOWN_COORDS
    } from "../assets/js/db.js";


    const svg = (d) => '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + d + '</svg>';
    const I = {
        eye: svg('<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>'),
        pin: svg('<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>'),
        warn: svg('<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5h.01"/>'),
        users: svg('<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17.5" cy="9" r="2.5"/><path d="M17 14.2c2.6.2 4.5 2.2 4.5 5.3"/>')
    };

    // ---------- element references ----------
    const rowsEl = document.getElementById("farmerRows");
    const footEl = document.getElementById("tableFoot");
    const searchInput = document.getElementById("searchInput");
    const statusFilter = document.getElementById("statusFilter");
    const townFilter = document.getElementById("townFilter");
    const refreshBtn = document.getElementById("refreshBtn");

    const detailsDlg = document.getElementById("detailsDlg");
    const confirmDlg = document.getElementById("confirmDlg");

    let farmers = [];


    // ---------- small helpers ----------
    function initials(name) {
        const parts = String(name || "?").trim().split(/\s+/).slice(0, 2);
        return parts.map(function (p) { return p.charAt(0).toUpperCase(); }).join("") || "?";
    }

    // Farm size: the number typed on the profile if there is one, otherwise the total area of the farmer's crops
    function sizeOf(f) {
        const typed = Number(f.farm_size);
        if (typed > 0) return typed;
        const sum = f.crops.reduce(function (n, c) { return n + (Number(c.area) || 0); }, 0);
        return Math.round(sum * 10) / 10;
    }
    function sizeText(f) { const n = sizeOf(f); return n > 0 ? n + " ha" : ""; }

    function locationOf(f) {
        if (!f.municipality) return "Not set";
        return (f.barangay ? "Brgy. " + f.barangay + ", " : "") + f.municipality;
    }

    function agoText(iso) {
        if (!iso) return "Never";
        const d = dayDiff(iso, todayISO());
        if (d <= 0) return "Today";
        if (d === 1) return "Yesterday";
        if (d < 30) return d + " days ago";
        return fmtFull(iso);
    }

    function toast(text) {
        const t = document.createElement("div");
        t.className = "mf-toast";
        t.setAttribute("role", "status");
        t.textContent = text;
        document.body.appendChild(t);
        setTimeout(function () { t.remove(); }, 2600);
    }

    // returns a promise that resolves true (confirmed) or false (cancelled)
    function confirmAction(title, text, okLabel, danger) {
        document.getElementById("confirmTitle").textContent = title;
        document.getElementById("confirmText").textContent = text;
        const ok = document.getElementById("confirmOk");
        ok.textContent = okLabel;
        ok.className = danger ? "mf-btn primary" : "mf-btn primary";
        ok.classList.toggle("danger-solid", !!danger);

        return new Promise(function (resolve) {
            const cancel = document.getElementById("confirmCancel");
            function done(result) {
                ok.removeEventListener("click", onOk);
                cancel.removeEventListener("click", onCancel);
                confirmDlg.removeEventListener("close", onCancel);
                if (confirmDlg.open) confirmDlg.close();
                resolve(result);
            }
            function onOk() { done(true); }
            function onCancel() { done(false); }
            ok.addEventListener("click", onOk);
            cancel.addEventListener("click", onCancel);
            confirmDlg.addEventListener("close", onCancel);
            confirmDlg.showModal();
        });
    }


    // ---------- load data ----------
    async function load() {
        refreshBtn.disabled = true;
        try {
            const results = await Promise.all([listAllUsers(), listAll("crops")]);
            const users = results[0];
            const cropsByUid = results[1] || {};

            farmers = users
                .filter(function (u) { return u.role !== "admin"; })
                .map(function (u) {
                    const crops = Object.entries(cropsByUid[u.id] || {}).map(function (entry) {
                        return Object.assign({}, entry[1], { id: entry[0] });
                    });
                    return Object.assign({}, u, { crops: crops });
                })
                .sort(function (a, b) { return String(a.name).localeCompare(String(b.name)); });

            fillTownFilter();
            render();
        } catch (err) {
            console.error(err);
            showError(explain(err, "Could not load the farmers."));
            rowsEl.innerHTML = '<tr><td colspan="8"><div class="mf-state"><span class="mf-ico">'+I.warn+'</span><b>Could not load farmers</b>Check the message above and your connection, then press Refresh.</div></td></tr>';
        } finally {
            refreshBtn.disabled = false;
        }
    }

    function fillTownFilter() {
        const current = townFilter.value;
        const towns = Array.from(new Set(farmers.map(function (f) { return f.municipality; }).filter(Boolean))).sort();
        townFilter.innerHTML = '<option value="all">All municipalities</option>' +
            towns.map(function (t) { return '<option value="' + esc(t) + '">' + esc(t) + "</option>"; }).join("");
        if (towns.indexOf(current) >= 0) townFilter.value = current;
    }


    // ---------- draw ----------
    function visibleFarmers() {
        const q = searchInput.value.trim().toLowerCase();
        const status = statusFilter.value;
        const town = townFilter.value;

        return farmers.filter(function (f) {
            if (status !== "all" && f.status !== status) return false;
            if (town !== "all" && f.municipality !== town) return false;
            if (!q) return true;
            const haystack = [f.name, f.email, f.municipality, f.barangay].join(" ").toLowerCase();
            return haystack.indexOf(q) >= 0;
        });
    }

    function render() {
        // stats always reflect everyone, not just the filtered rows
        const active = farmers.filter(function (f) { return f.status === "active"; }).length;
        document.getElementById("statTotal").textContent = farmers.length;
        document.getElementById("statActive").textContent = active;
        document.getElementById("statInactive").textContent = farmers.length - active;
        document.getElementById("statCrops").textContent = farmers.reduce(function (n, f) { return n + f.crops.length; }, 0);

        const list = visibleFarmers();

        if (!list.length) {
            const none = !farmers.length;
            rowsEl.innerHTML = '<tr><td colspan="8"><div class="mf-state"><span class="mf-ico">'+I.users+'</span><b>' +
                (none ? "No farmers yet" : "No farmers match your search") + "</b>" +
                (none ? "Farmer accounts appear here as soon as people register." : "Try a different name, status or municipality.") +
                "</div></td></tr>";
            footEl.textContent = "";
            return;
        }

        rowsEl.innerHTML = list.map(function (f) {
            const isActive = f.status === "active";
            return "<tr>" +
                '<td><div class="mf-person"><div class="mf-avatar" aria-hidden="true">' + esc(initials(f.name)) + "</div>" +
                    "<div><b>" + esc(f.name) + "</b><small>" + esc(f.email || "No email") + "</small></div></div></td>" +
                "<td>" + esc(locationOf(f)) + "</td>" +
                '<td class="mf-num mf-nowrap">' + (sizeText(f) ? esc(sizeText(f)) : '<span class="mf-muted">-</span>') + "</td>" +
                '<td class="mf-num">' + f.crops.length + "</td>" +
                '<td class="mf-nowrap">' + esc(f.joined ? fmtFull(f.joined) : "-") + "</td>" +
                '<td class="mf-nowrap">' + esc(agoText(f.lastActive)) + "</td>" +
                '<td><span class="mf-pill ' + (isActive ? "active" : "inactive") + '">' + (isActive ? "Active" : "Deactivated") + "</span></td>" +
                '<td class="mf-actions">' +
                    '<button type="button" class="mf-btn small" data-view="' + esc(f.id) + '" title="View profile">'+I.eye+' View</button>' +
                    '<button type="button" class="mf-btn small icon" data-loc="' + esc(f.id) + '" title="Change farm location" aria-label="Change farm location of ' + esc(f.name) + '">' + I.pin + '</button>' +
                    '<button type="button" class="mf-btn small ' + (isActive ? "danger" : "") + '" data-toggle="' + esc(f.id) + '">' +
                        (isActive ? "Deactivate" : "Activate") + "</button>" +
                "</td></tr>";
        }).join("");

        footEl.textContent = "Showing " + list.length + " of " + farmers.length + " farmer" + (farmers.length === 1 ? "" : "s") + ".";
    }


    // ---------- actions ----------
    function showDetails(f) {
        document.getElementById("detailsAvatar").textContent = initials(f.name);
        document.getElementById("detailsName").textContent = f.name;
        document.getElementById("detailsEmail").textContent = f.email || "No email on file";

        const rows = [
            ["Status", f.status === "active" ? "Active" : "Deactivated"],
            ["Farm location", locationOf(f)],
            ["Location type", f.locationSource === "gps" ? "Exact GPS pin" : f.municipality ? "Town centre" : "Not set"],
            ["Farm size", sizeText(f) ? sizeText(f) + (Number(f.farm_size) > 0 ? "" : " (from crops)") : "Not provided"],
            ["Joined", f.joined ? fmtFull(f.joined) : "Unknown"],
            ["Last active", agoText(f.lastActive)],
            ["Registered crops", String(f.crops.length)]
        ];
        document.getElementById("detailsGrid").innerHTML = rows.map(function (r) {
            return "<div><dt>" + esc(r[0]) + "</dt><dd>" + esc(r[1]) + "</dd></div>";
        }).join("");

        const cropsEl = document.getElementById("detailsCrops");
        cropsEl.innerHTML = f.crops.length
            ? f.crops.map(function (c) {
                const state = c.harvestedOn ? "Harvested " + fmtFull(c.harvestedOn) : c.harvest ? "Harvest " + fmtFull(c.harvest) : "";
                return "<li><b>" + esc(c.name || "Crop") + (c.field ? " (" + esc(c.field) + ")" : "") + "</b><span>" +
                    (c.area ? esc(c.area) + " ha" : "") + (c.area && state ? " &middot; " : "") + esc(state) + "</span></li>";
            }).join("")
            : '<li><span style="text-align:left">This farmer has not registered any crops yet.</span></li>';

        detailsDlg.showModal();
    }

    // ---------- change a farmer's farm location (for when they picked the wrong town) ----------
    const locDlg = document.getElementById("locDlg");
    const locTown = document.getElementById("locTown");
    const locBrgy = document.getElementById("locBrgy");
    let locFarmer = null;

    function editLocation(f) {
        locFarmer = f;
        document.getElementById("locSub").textContent = f.name + " is currently set to: " + locationOf(f) + ".";
        locTown.innerHTML = '<option value="" disabled>Choose a municipality</option>' +
            Object.keys(TOWN_COORDS).map(function (t) { return '<option value="' + esc(t) + '">' + esc(t) + "</option>"; }).join("");
        locTown.value = TOWN_COORDS[f.municipality] ? f.municipality : "";
        locBrgy.value = f.barangay || "";
        document.getElementById("locSize").value = Number(f.farm_size) > 0 ? f.farm_size : "";
        locDlg.showModal();
    }

    async function saveLocation(ev) {
        ev.preventDefault();
        const f = locFarmer;
        const town = locTown.value;
        if (!f || !TOWN_COORDS[town]) return;
        const patch = {
            municipality: town,
            barangay: locBrgy.value.trim(),
            lat: TOWN_COORDS[town][0],
            lon: TOWN_COORDS[town][1],
            locationSource: "town"
        };
        const size = parseFloat(document.getElementById("locSize").value);
        if (size > 0) patch.farm_size = size;
        const saveBtn = document.getElementById("locSave");
        saveBtn.disabled = true;
        try {
            await updateUser(f.id, patch);
            Object.assign(f, patch);
            locDlg.close();
            fillTownFilter();
            render();
            toast(f.name + "'s farm location is now " + locationOf(f) + ".");
        } catch (err) {
            console.error(err);
            toast("Could not update. " + (err && err.code === "PERMISSION_DENIED" ? "Your database rules do not allow admins to change locations." : "Check your connection."));
        } finally {
            saveBtn.disabled = false;
        }
    }

    async function toggleStatus(f) {
        const next = f.status === "active" ? "inactive" : "active";
        const deactivating = next === "inactive";

        const yes = await confirmAction(
            deactivating ? "Deactivate " + f.name + "?" : "Activate " + f.name + "?",
            deactivating
                ? "They will be signed out and will not be able to sign in again until you activate the account. Their data is kept."
                : "They will be able to sign in again right away.",
            deactivating ? "Deactivate" : "Activate",
            deactivating
        );
        if (!yes) return;

        try {
            await updateUser(f.id, { status: next });
            f.status = next;
            render();
            toast(f.name + (deactivating ? " was deactivated." : " was activated."));
        } catch (err) {
            console.error(err);
            toast("Could not update. " + (err && err.code === "PERMISSION_DENIED" ? "Your database rules do not allow admins to edit users." : "Check your connection."));
        }
    }


    // ---------- events ----------
    rowsEl.addEventListener("click", function (event) {
        const viewBtn = event.target.closest("[data-view]");
        const toggleBtn = event.target.closest("[data-toggle]");
        const locBtn = event.target.closest("[data-loc]");
        if (locBtn) {
            const f = farmers.find(function (x) { return x.id === locBtn.dataset.loc; });
            if (f) editLocation(f);
        } else if (viewBtn) {
            const f = farmers.find(function (x) { return x.id === viewBtn.dataset.view; });
            if (f) showDetails(f);
        } else if (toggleBtn) {
            const f = farmers.find(function (x) { return x.id === toggleBtn.dataset.toggle; });
            if (f) toggleStatus(f);
        }
    });

    [searchInput, statusFilter, townFilter].forEach(function (el) {
        el.addEventListener("input", render);
    });

    refreshBtn.addEventListener("click", load);

    document.getElementById("locForm").addEventListener("submit", saveLocation);
    document.getElementById("locCancel").addEventListener("click", function () { locDlg.close(); });

    detailsDlg.querySelector("[data-close]").addEventListener("click", function () { detailsDlg.close(); });

    // clicking the dark backdrop closes a dialog
    [detailsDlg, confirmDlg, locDlg].forEach(function (dlg) {
        dlg.addEventListener("click", function (event) {
            if (event.target === dlg) dlg.close();
        });
    });

    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) logoutBtn.addEventListener("click", signOutAndGo);


    // ---------- start: only admins get past this line ----------
    requireUser({ role: "admin" }).then(load);

</script>
</body>
</html>