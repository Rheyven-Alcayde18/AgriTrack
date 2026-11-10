<?php
// admin/farmers-data.php
// A data overview of all farmers: where they are, what they grow, how much they harvest.
// Three charts on top, then a sortable table with one row of numbers per farmer.

$pageTitle  = 'Farmers data';
$active     = 'farmers-data';
$heading    = 'Farmers data';
$subtitle   = 'Where your farmers are, what they grow, and how much they harvest.';
$topActions = '<button type="button" class="btn" id="refreshBtn"><i class="ph-bold ph-arrow-clockwise" aria-hidden="true"></i> Refresh</button>' .
              '<a class="btn primary" href="reports.php"><i class="ph-bold ph-download-simple" aria-hidden="true"></i> Download reports</a>';

require __DIR__ . '/includes/header.php';
?>

        <!-- STATS -->
        <section class="stats" aria-label="Summary">
            <div class="stat"><i class="ph-duotone ph-users-three" aria-hidden="true"></i><div><b id="statFarmers">0</b><span>Farmers</span></div></div>
            <div class="stat"><i class="ph-duotone ph-map-trifold" aria-hidden="true"></i><div><b id="statTowns">0</b><span>Municipalities</span></div></div>
            <div class="stat"><i class="ph-duotone ph-ruler" aria-hidden="true"></i><div><b id="statSize">0</b><span>Average farm size (ha)</span></div></div>
            <div class="stat"><i class="ph-duotone ph-grains" aria-hidden="true"></i><div><b id="statYield">0</b><span>Harvested (tonnes)</span></div></div>
            <div class="stat"><i class="ph-duotone ph-coins" aria-hidden="true"></i><div><b id="statRevenue">0</b><span>Harvest revenue</span></div></div>
        </section>

        <!-- CHARTS -->
        <section class="charts" aria-label="Charts">
            <div class="panel">
                <h2>Farmers by municipality</h2>
                <div id="chartTowns"></div>
            </div>
            <div class="panel">
                <h2>Crops by type</h2>
                <div id="chartCrops"></div>
            </div>
            <div class="panel">
                <h2>New farmers, last 6 months</h2>
                <div class="cols" id="chartJoined"></div>
            </div>
        </section>

        <!-- SEARCH -->
        <div class="toolbar">
            <div class="search">
                <i class="ph-duotone ph-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="searchInput" placeholder="Search by name or municipality" aria-label="Search farmers" autocomplete="off">
            </div>
        </div>

        <!-- TABLE -->
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr id="headRow">
                            <th data-sort="name">Farmer</th>
                            <th data-sort="municipality">Municipality</th>
                            <th data-sort="farm_size" class="num">Farm size (ha)</th>
                            <th data-sort="crops" class="num">Crops</th>
                            <th data-sort="cropArea" class="num">Crop area (ha)</th>
                            <th data-sort="harvests" class="num">Harvests</th>
                            <th data-sort="harvestYield" class="num">Yield (t)</th>
                            <th data-sort="harvestRevenue" class="right">Revenue</th>
                            <th data-sort="joined">Joined</th>
                        </tr>
                    </thead>
                    <tbody id="dataRows">
                        <tr><td colspan="9"><div class="state"><i class="ph-duotone ph-spinner-gap" aria-hidden="true"></i><b>Loading data...</b>Reading records from Firebase.</div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="pager" id="pager"></div>
            <div class="foot" id="tableFoot" aria-live="polite"></div>
        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<script type="module">

    import { requireUser, esc, fmtFull, peso, num, todayISO, signOutAndGo, explain, showError } from "../assets/js/db.js";
    import { loadAdminData, initials, renderPager, clampPage } from "../assets/js/admin-data.js";

    const PAGE_SIZE = 10;
    const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

    const rowsEl = document.getElementById("dataRows");
    const footEl = document.getElementById("tableFoot");
    const pagerEl = document.getElementById("pager");
    const searchInput = document.getElementById("searchInput");
    const refreshBtn = document.getElementById("refreshBtn");
    const headRow = document.getElementById("headRow");

    let farmers = [];
    let page = 1;
    let sortKey = "name";
    let sortDir = 1; // 1 ascending, -1 descending


    // ---------- load ----------
    async function load() {
        refreshBtn.disabled = true;
        try {
            const data = await loadAdminData();
            farmers = data.farmers.map((f) => Object.assign({}, f, {
                cropsCount: f.crops.length,
                harvestsCount: f.harvests.length
            }));
            drawSummary(data);
            drawCharts(data);
            render();
        } catch (err) {
            console.error(err);
            showError(explain(err, "Could not load the farmers data."));
            rowsEl.innerHTML = '<tr><td colspan="9"><div class="state"><i class="ph-duotone ph-warning-circle" aria-hidden="true"></i><b>Could not load the data</b>Check the message above and your connection, then press Refresh.</div></td></tr>';
        } finally {
            refreshBtn.disabled = false;
        }
    }


    // ---------- summary + charts ----------
    function drawSummary(data) {
        const f = data.farmers;
        const towns = new Set(f.map((x) => x.municipality).filter(Boolean));
        const sizes = f.map((x) => Number(x.farm_size) || 0).filter((n) => n > 0);
        const avg = sizes.length ? sizes.reduce((a, b) => a + b, 0) / sizes.length : 0;
        const yieldT = data.harvests.reduce((n, h) => n + h.yield_t, 0);
        const revenue = data.harvests.reduce((n, h) => n + h.revenue, 0);

        document.getElementById("statFarmers").textContent = f.length;
        document.getElementById("statTowns").textContent = towns.size;
        document.getElementById("statSize").textContent = num(avg);
        document.getElementById("statYield").textContent = num(yieldT);
        document.getElementById("statRevenue").textContent = peso(revenue);
    }

    function barList(el, entries, emptyText) {
        if (!entries.length) {
            el.innerHTML = '<p class="empty">' + esc(emptyText) + "</p>";
            return;
        }
        const max = Math.max.apply(null, entries.map((e) => e[1]));
        el.innerHTML = entries.map((e) =>
            '<div class="bar-row"><span title="' + esc(e[0]) + '">' + esc(e[0]) + "</span>" +
            '<div class="bar-track"><div class="bar-fill" style="width:' + Math.max(4, (e[1] / max) * 100) + '%"></div></div>' +
            "<b>" + e[1] + "</b></div>"
        ).join("");
    }

    function tally(items, keyOf) {
        const map = {};
        items.forEach((item) => {
            const key = keyOf(item);
            if (key) map[key] = (map[key] || 0) + 1;
        });
        return Object.entries(map).sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0])).slice(0, 8);
    }

    function drawCharts(data) {
        barList(document.getElementById("chartTowns"), tally(data.farmers, (f) => f.municipality), "No farm locations yet.");
        barList(document.getElementById("chartCrops"), tally(data.crops, (c) => c.name), "No crops registered yet.");

        // last 6 calendar months, oldest first
        const [y, m] = todayISO().split("-").map(Number);
        const months = [];
        for (let i = 5; i >= 0; i--) {
            const d = new Date(Date.UTC(y, m - 1 - i, 1));
            const key = d.getUTCFullYear() + "-" + String(d.getUTCMonth() + 1).padStart(2, "0");
            months.push({ key: key, label: MONTHS[d.getUTCMonth()], count: 0 });
        }
        data.farmers.forEach((f) => {
            const hit = months.find((mo) => f.joined && f.joined.indexOf(mo.key) === 0);
            if (hit) hit.count++;
        });
        const max = Math.max(1, Math.max.apply(null, months.map((mo) => mo.count)));
        document.getElementById("chartJoined").innerHTML = months.map((mo) =>
            '<div class="col"><b>' + mo.count + '</b><i style="height:' + Math.max(3, (mo.count / max) * 100) + '%"></i><span>' + mo.label + "</span></div>"
        ).join("");
    }


    // ---------- table ----------
    function valueOf(f, key) {
        switch (key) {
            case "crops":    return f.cropsCount;
            case "harvests": return f.harvestsCount;
            case "farm_size": return Number(f.farm_size) || 0;
            case "cropArea":
            case "harvestYield":
            case "harvestRevenue": return Number(f[key]) || 0;
            case "joined":   return f.joined || "";
            default:         return String(f[key] || "").toLowerCase();
        }
    }

    function visible() {
        const q = searchInput.value.trim().toLowerCase();
        const list = farmers.filter((f) => !q || [f.name, f.municipality, f.barangay].join(" ").toLowerCase().indexOf(q) >= 0);
        list.sort((a, b) => {
            const x = valueOf(a, sortKey);
            const y = valueOf(b, sortKey);
            if (x < y) return -1 * sortDir;
            if (x > y) return 1 * sortDir;
            return String(a.name).localeCompare(String(b.name));
        });
        return list;
    }

    function markSort() {
        headRow.querySelectorAll("th[data-sort]").forEach((th) => {
            th.setAttribute("aria-sort", th.dataset.sort === sortKey ? (sortDir === 1 ? "ascending" : "descending") : "none");
        });
    }

    function render() {
        markSort();
        const list = visible();
        page = clampPage(page, list.length, PAGE_SIZE);

        if (!list.length) {
            rowsEl.innerHTML = '<tr><td colspan="9"><div class="state"><i class="ph-duotone ph-database" aria-hidden="true"></i><b>' +
                (farmers.length ? "No farmers match your search" : "No data yet") + "</b>" +
                (farmers.length ? "Try a different name or municipality." : "Numbers appear here once farmers register and add crops.") +
                "</div></td></tr>";
            footEl.textContent = "";
            pagerEl.innerHTML = "";
            return;
        }

        const slice = list.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

        rowsEl.innerHTML = slice.map((f) =>
            '<tr><td><div class="person"><div class="avatar" aria-hidden="true">' + esc(initials(f.name)) + "</div>" +
                "<div><b>" + esc(f.name) + "</b><small>" + esc(f.email || "No email") + "</small></div></div></td>" +
            "<td>" + esc(f.municipality || "Not set") + (f.barangay ? '<div class="muted" style="font-size:12.5px">Brgy. ' + esc(f.barangay) + "</div>" : "") + "</td>" +
            '<td class="num">' + (Number(f.farm_size) ? esc(num(f.farm_size)) : '<span class="muted">-</span>') + "</td>" +
            '<td class="num">' + f.cropsCount + "</td>" +
            '<td class="num">' + esc(num(f.cropArea)) + "</td>" +
            '<td class="num">' + f.harvestsCount + "</td>" +
            '<td class="num">' + esc(num(f.harvestYield)) + "</td>" +
            '<td class="right">' + esc(peso(f.harvestRevenue)) + "</td>" +
            "<td>" + esc(f.joined ? fmtFull(f.joined) : "-") + "</td></tr>"
        ).join("");

        footEl.textContent = "Showing " + list.length + " of " + farmers.length + " farmer" + (farmers.length === 1 ? "" : "s") + ". Click a column title to sort.";
        renderPager(pagerEl, list.length, page, PAGE_SIZE, (p) => { page = p; render(); });
    }


    // ---------- events ----------
    headRow.addEventListener("click", (event) => {
        const th = event.target.closest("th[data-sort]");
        if (!th) return;
        if (sortKey === th.dataset.sort) {
            sortDir = -sortDir;
        } else {
            sortKey = th.dataset.sort;
            sortDir = 1;
        }
        page = 1;
        render();
    });

    searchInput.addEventListener("input", () => { page = 1; render(); });
    refreshBtn.addEventListener("click", load);
    document.getElementById("logoutBtn").addEventListener("click", signOutAndGo);

    // only admins get past this line
    requireUser({ role: "admin" }).then(load);

</script>

</body>
</html>