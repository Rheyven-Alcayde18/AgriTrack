<?php
// admin/manage-crops.php
// Every crop registered by every farmer: search, filter, see stage and status,
// open the details, and remove a crop when needed.

$pageTitle  = 'Manage crops';
$active     = 'manage-crops';
$heading    = 'Manage crops';
$subtitle   = 'All crops across all farms, with their growth stage, soil moisture and harvest dates.';
$topActions = '<button type="button" class="btn" id="refreshBtn"><i class="ph-bold ph-arrow-clockwise" aria-hidden="true"></i> Refresh</button>';

require __DIR__ . '/includes/header.php';
?>

        <!-- STATS -->
        <section class="stats" aria-label="Summary">
            <div class="stat"><i class="ph-duotone ph-plant" aria-hidden="true"></i><div><b id="statTotal">0</b><span>Total crops</span></div></div>
            <div class="stat"><i class="ph-duotone ph-leaf" aria-hidden="true"></i><div><b id="statOk">0</b><span>On track</span></div></div>
            <div class="stat"><i class="ph-duotone ph-basket" aria-hidden="true"></i><div><b id="statSoon">0</b><span>Harvest soon</span></div></div>
            <div class="stat"><i class="ph-duotone ph-drop" aria-hidden="true"></i><div><b id="statWarn">0</b><span>Needs water</span></div></div>
            <div class="stat"><i class="ph-duotone ph-check-circle" aria-hidden="true"></i><div><b id="statDone">0</b><span>Harvested</span></div></div>
        </section>

        <!-- SEARCH + FILTERS -->
        <div class="toolbar">
            <div class="search">
                <i class="ph-duotone ph-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="searchInput" placeholder="Search by crop, field or farmer" aria-label="Search crops" autocomplete="off">
            </div>

            <select id="statusFilter" aria-label="Filter by status">
                <option value="all">All statuses</option>
                <option value="ok">On track</option>
                <option value="soon">Harvest soon</option>
                <option value="warn">Needs water</option>
                <option value="done">Harvested</option>
            </select>

            <select id="cropFilter" aria-label="Filter by crop">
                <option value="all">All crops</option>
            </select>

            <select id="townFilter" aria-label="Filter by municipality">
                <option value="all">All municipalities</option>
            </select>
        </div>

        <!-- TABLE -->
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Crop</th>
                            <th>Farmer</th>
                            <th class="num">Area</th>
                            <th>Planted</th>
                            <th>Harvest</th>
                            <th>Growth</th>
                            <th class="num">Moisture</th>
                            <th>Status</th>
                            <th class="right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cropRows">
                        <tr><td colspan="9"><div class="state"><i class="ph-duotone ph-spinner-gap" aria-hidden="true"></i><b>Loading crops...</b>Reading records from Firebase.</div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="pager" id="pager"></div>
            <div class="foot" id="tableFoot" aria-live="polite"></div>
        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<!-- CROP DETAILS -->
<dialog id="detailsDlg" aria-labelledby="detailsName">
    <div class="dlg">
        <div class="dlg-head">
            <div class="avatar" id="detailsAvatar"></div>
            <div>
                <h2 id="detailsName"></h2>
                <p class="sub" id="detailsSub"></p>
            </div>
        </div>

        <dl class="grid2" id="detailsGrid"></dl>

        <h3>Recommended action</h3>
        <p class="note" id="detailsAction"></p>

        <div class="dlg-foot">
            <button type="button" class="btn" data-close>Close</button>
        </div>
    </div>
</dialog>

<script type="module">

    import { requireUser, deleteCrop, esc, fmtFull, daysText, num, peso, signOutAndGo, explain, showError } from "../assets/js/db.js";
    import { loadAdminData, initials, toast, confirmAction, closeOnBackdrop, renderPager, clampPage } from "../assets/js/admin-data.js";

    const PAGE_SIZE = 15;

    const rowsEl = document.getElementById("cropRows");
    const footEl = document.getElementById("tableFoot");
    const pagerEl = document.getElementById("pager");
    const searchInput = document.getElementById("searchInput");
    const statusFilter = document.getElementById("statusFilter");
    const cropFilter = document.getElementById("cropFilter");
    const townFilter = document.getElementById("townFilter");
    const refreshBtn = document.getElementById("refreshBtn");
    const detailsDlg = document.getElementById("detailsDlg");

    let crops = [];
    let page = 1;


    // ---------- load ----------
    async function load() {
        refreshBtn.disabled = true;
        try {
            const data = await loadAdminData();
            crops = data.crops.slice().sort((a, b) => String(a.harvest || "").localeCompare(String(b.harvest || "")));
            fillFilters();
            render();
        } catch (err) {
            console.error(err);
            showError(explain(err, "Could not load the crops."));
            rowsEl.innerHTML = '<tr><td colspan="9"><div class="state"><i class="ph-duotone ph-warning-circle" aria-hidden="true"></i><b>Could not load crops</b>Check the message above and your connection, then press Refresh.</div></td></tr>';
        } finally {
            refreshBtn.disabled = false;
        }
    }

    function fillOptions(select, firstLabel, values) {
        const current = select.value;
        select.innerHTML = '<option value="all">' + esc(firstLabel) + "</option>" +
            values.map((v) => '<option value="' + esc(v) + '">' + esc(v) + "</option>").join("");
        if (values.indexOf(current) >= 0) select.value = current;
    }

    function fillFilters() {
        const names = Array.from(new Set(crops.map((c) => c.name).filter(Boolean))).sort();
        const towns = Array.from(new Set(crops.map((c) => c.municipality).filter(Boolean))).sort();
        fillOptions(cropFilter, "All crops", names);
        fillOptions(townFilter, "All municipalities", towns);
    }


    // ---------- draw ----------
    function visible() {
        const q = searchInput.value.trim().toLowerCase();
        const status = statusFilter.value;
        const crop = cropFilter.value;
        const town = townFilter.value;
        return crops.filter((c) => {
            if (status !== "all" && c.status !== status) return false;
            if (crop !== "all" && c.name !== crop) return false;
            if (town !== "all" && c.municipality !== town) return false;
            if (!q) return true;
            return [c.name, c.field, c.farmer].join(" ").toLowerCase().indexOf(q) >= 0;
        });
    }

    function count(status) { return crops.filter((c) => c.status === status).length; }

    function render() {
        document.getElementById("statTotal").textContent = crops.length;
        document.getElementById("statOk").textContent = count("ok");
        document.getElementById("statSoon").textContent = count("soon");
        document.getElementById("statWarn").textContent = count("warn");
        document.getElementById("statDone").textContent = count("done");

        const list = visible();
        page = clampPage(page, list.length, PAGE_SIZE);

        if (!list.length) {
            const none = !crops.length;
            rowsEl.innerHTML = '<tr><td colspan="9"><div class="state"><i class="ph-duotone ph-plant" aria-hidden="true"></i><b>' +
                (none ? "No crops yet" : "No crops match your search") + "</b>" +
                (none ? "Crops appear here when farmers register them." : "Try a different crop, status or municipality.") +
                "</div></td></tr>";
            footEl.textContent = "";
            pagerEl.innerHTML = "";
            return;
        }

        const slice = list.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

        rowsEl.innerHTML = slice.map((c) =>
            '<tr><td><div class="stack"><b>' + esc(c.name || "Crop") + "</b><small>" + esc(c.field || "No field name") + "</small></div></td>" +
            '<td><div class="stack"><b>' + esc(c.farmer) + "</b><small>" + esc(c.municipality || "Location not set") + "</small></div></td>" +
            '<td class="num">' + (c.area ? esc(num(c.area)) + " ha" : '<span class="muted">-</span>') + "</td>" +
            "<td>" + esc(c.planted ? fmtFull(c.planted) : "-") + "</td>" +
            "<td>" + esc(c.harvestedOn ? fmtFull(c.harvestedOn) : c.harvest ? fmtFull(c.harvest) : "-") + "</td>" +
            '<td class="progress-cell"><div class="mini" title="' + c.progress + '%"><i style="width:' + c.progress + '%"></i></div><small>' + esc(c.stage) + "</small></td>" +
            '<td class="num">' + esc(num(c.moisture)) + "%</td>" +
            '<td><span class="pill ' + esc(c.status) + '">' + esc(c.label) + "</span></td>" +
            '<td class="actions">' +
                '<button type="button" class="btn small" data-view="' + esc(c.uid + "|" + c.id) + '"><i class="ph-bold ph-eye" aria-hidden="true"></i> View</button>' +
                '<button type="button" class="btn small danger" data-delete="' + esc(c.uid + "|" + c.id) + '">Remove</button>' +
            "</td></tr>"
        ).join("");

        footEl.textContent = "Showing " + list.length + " of " + crops.length + " crop" + (crops.length === 1 ? "" : "s") + ".";
        renderPager(pagerEl, list.length, page, PAGE_SIZE, (p) => { page = p; render(); });
    }


    // ---------- actions ----------
    function findCrop(key) {
        const parts = String(key).split("|");
        return crops.find((c) => c.uid === parts[0] && c.id === parts[1]);
    }

    function showDetails(c) {
        document.getElementById("detailsAvatar").textContent = initials(c.name);
        document.getElementById("detailsName").textContent = (c.name || "Crop") + (c.field ? " (" + c.field + ")" : "");
        document.getElementById("detailsSub").textContent = "Grown by " + c.farmer + (c.municipality ? ", " + c.municipality : "");

        const rows = [
            ["Status", c.label],
            ["Growth stage", c.stage + " (" + c.progress + "%)"],
            ["Area", c.area ? num(c.area) + " ha" : "Not provided"],
            ["Soil moisture", num(c.moisture) + "%"],
            ["Planted", c.planted ? fmtFull(c.planted) : "Unknown"],
            [c.harvestedOn ? "Harvested on" : "Expected harvest", c.harvestedOn ? fmtFull(c.harvestedOn) : c.harvest ? fmtFull(c.harvest) : "Unknown"],
            ["Time to harvest", c.harvestedOn ? "Done" : c.harvest ? daysText(c.days) : "Unknown"],
            ["Expected yield", c.yield_t ? num(c.yield_t) + " t" : "Not provided"],
            ["Price", c.price ? "PHP " + num(c.price) + " per kg" : "Not provided"],
            ["Estimated revenue", c.revenue ? peso(c.revenue) : "Not provided"]
        ];
        document.getElementById("detailsGrid").innerHTML = rows.map((r) =>
            "<div><dt>" + esc(r[0]) + "</dt><dd>" + esc(r[1]) + "</dd></div>").join("");

        document.getElementById("detailsAction").textContent = c.action || "No recommendation available.";
        detailsDlg.showModal();
    }

    async function removeCrop(c) {
        const yes = await confirmAction(
            "Remove this crop?",
            c.farmer + "'s " + String(c.name || "crop").toLowerCase() + (c.field ? " in " + c.field : "") + " will be deleted. This cannot be undone.",
            "Remove crop",
            true
        );
        if (!yes) return;

        try {
            await deleteCrop(c.uid, c);
            crops = crops.filter((x) => !(x.uid === c.uid && x.id === c.id));
            fillFilters();
            render();
            toast("Crop removed.");
        } catch (err) {
            console.error(err);
            toast("Could not remove it. " + (err && err.code === "PERMISSION_DENIED"
                ? "Your database rules do not allow admins to delete crops."
                : "Check your connection."));
        }
    }


    // ---------- events ----------
    rowsEl.addEventListener("click", (event) => {
        const viewBtn = event.target.closest("[data-view]");
        const delBtn = event.target.closest("[data-delete]");
        if (viewBtn) {
            const c = findCrop(viewBtn.dataset.view);
            if (c) showDetails(c);
        } else if (delBtn) {
            const c = findCrop(delBtn.dataset.delete);
            if (c) removeCrop(c);
        }
    });

    [searchInput, statusFilter, cropFilter, townFilter].forEach((el) => {
        el.addEventListener("input", () => { page = 1; render(); });
    });

    refreshBtn.addEventListener("click", load);
    detailsDlg.querySelector("[data-close]").addEventListener("click", () => detailsDlg.close());
    closeOnBackdrop(detailsDlg);
    document.getElementById("logoutBtn").addEventListener("click", signOutAndGo);

    // only admins get past this line
    requireUser({ role: "admin" }).then(load);

</script>

</body>
</html>