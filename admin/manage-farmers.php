<?php
// admin/manage-farmers.php
// See every farmer account, search and filter them, view their crops,
// and activate or deactivate accounts.

$pageTitle  = 'Manage farmers';
$active     = 'manage-farmers';
$heading    = 'Manage farmers';
$subtitle   = 'View every farmer account, check their farms and crops, and switch accounts on or off.';
$topActions = '<button type="button" class="btn" id="refreshBtn"><i class="ph-bold ph-arrow-clockwise" aria-hidden="true"></i> Refresh</button>';

require __DIR__ . '/includes/header.php';
?>

        <!-- STATS -->
        <section class="stats" aria-label="Summary">
            <div class="stat"><i class="ph-duotone ph-users-three" aria-hidden="true"></i><div><b id="statTotal">0</b><span>Total farmers</span></div></div>
            <div class="stat"><i class="ph-duotone ph-user-check" aria-hidden="true"></i><div><b id="statActive">0</b><span>Active</span></div></div>
            <div class="stat"><i class="ph-duotone ph-user-minus" aria-hidden="true"></i><div><b id="statInactive">0</b><span>Deactivated</span></div></div>
            <div class="stat"><i class="ph-duotone ph-plant" aria-hidden="true"></i><div><b id="statCrops">0</b><span>Registered crops</span></div></div>
        </section>

        <!-- SEARCH + FILTERS -->
        <div class="toolbar">
            <div class="search">
                <i class="ph-duotone ph-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="searchInput" placeholder="Search by name, email or location" aria-label="Search farmers" autocomplete="off">
            </div>

            <select id="statusFilter" aria-label="Filter by status">
                <option value="all">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Deactivated</option>
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
                            <th>Farmer</th>
                            <th>Farm location</th>
                            <th class="num">Farm size</th>
                            <th class="num">Crops</th>
                            <th>Joined</th>
                            <th>Last active</th>
                            <th>Status</th>
                            <th class="right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="farmerRows">
                        <tr><td colspan="8"><div class="state"><i class="ph-duotone ph-spinner-gap" aria-hidden="true"></i><b>Loading farmers...</b>Reading accounts from Firebase.</div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="pager" id="pager"></div>
            <div class="foot" id="tableFoot" aria-live="polite"></div>
        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<!-- FARMER DETAILS -->
<dialog id="detailsDlg" aria-labelledby="detailsName">
    <div class="dlg">
        <div class="dlg-head">
            <div class="avatar" id="detailsAvatar"></div>
            <div>
                <h2 id="detailsName"></h2>
                <p class="sub" id="detailsEmail"></p>
            </div>
        </div>

        <dl class="grid2" id="detailsGrid"></dl>

        <h3>Crops</h3>
        <ul class="croplist" id="detailsCrops"></ul>

        <div class="dlg-foot">
            <button type="button" class="btn" data-close>Close</button>
        </div>
    </div>
</dialog>

<script type="module">

    import { requireUser, updateUser, esc, fmtFull, signOutAndGo, explain, showError } from "../assets/js/db.js";
    import { loadAdminData, initials, locationOf, agoText, toast, confirmAction, closeOnBackdrop, renderPager, clampPage } from "../assets/js/admin-data.js";

    const PAGE_SIZE = 15;

    const rowsEl = document.getElementById("farmerRows");
    const footEl = document.getElementById("tableFoot");
    const pagerEl = document.getElementById("pager");
    const searchInput = document.getElementById("searchInput");
    const statusFilter = document.getElementById("statusFilter");
    const townFilter = document.getElementById("townFilter");
    const refreshBtn = document.getElementById("refreshBtn");
    const detailsDlg = document.getElementById("detailsDlg");

    let farmers = [];
    let page = 1;


    // ---------- load ----------
    async function load() {
        refreshBtn.disabled = true;
        try {
            const data = await loadAdminData();
            farmers = data.farmers;
            fillTownFilter();
            render();
        } catch (err) {
            console.error(err);
            showError(explain(err, "Could not load the farmers."));
            rowsEl.innerHTML = '<tr><td colspan="8"><div class="state"><i class="ph-duotone ph-warning-circle" aria-hidden="true"></i><b>Could not load farmers</b>Check the message above and your connection, then press Refresh.</div></td></tr>';
        } finally {
            refreshBtn.disabled = false;
        }
    }

    function fillTownFilter() {
        const current = townFilter.value;
        const towns = Array.from(new Set(farmers.map((f) => f.municipality).filter(Boolean))).sort();
        townFilter.innerHTML = '<option value="all">All municipalities</option>' +
            towns.map((t) => '<option value="' + esc(t) + '">' + esc(t) + "</option>").join("");
        if (towns.indexOf(current) >= 0) townFilter.value = current;
    }


    // ---------- draw ----------
    function visible() {
        const q = searchInput.value.trim().toLowerCase();
        const status = statusFilter.value;
        const town = townFilter.value;
        return farmers.filter((f) => {
            if (status !== "all" && f.status !== status) return false;
            if (town !== "all" && f.municipality !== town) return false;
            if (!q) return true;
            return [f.name, f.email, f.municipality, f.barangay].join(" ").toLowerCase().indexOf(q) >= 0;
        });
    }

    function render() {
        const active = farmers.filter((f) => f.status === "active").length;
        document.getElementById("statTotal").textContent = farmers.length;
        document.getElementById("statActive").textContent = active;
        document.getElementById("statInactive").textContent = farmers.length - active;
        document.getElementById("statCrops").textContent = farmers.reduce((n, f) => n + f.crops.length, 0);

        const list = visible();
        page = clampPage(page, list.length, PAGE_SIZE);

        if (!list.length) {
            const none = !farmers.length;
            rowsEl.innerHTML = '<tr><td colspan="8"><div class="state"><i class="ph-duotone ph-users-three" aria-hidden="true"></i><b>' +
                (none ? "No farmers yet" : "No farmers match your search") + "</b>" +
                (none ? "Farmer accounts appear here as soon as people register." : "Try a different name, status or municipality.") +
                "</div></td></tr>";
            footEl.textContent = "";
            pagerEl.innerHTML = "";
            return;
        }

        const slice = list.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

        rowsEl.innerHTML = slice.map((f) => {
            const isActive = f.status === "active";
            return "<tr>" +
                '<td><div class="person"><div class="avatar" aria-hidden="true">' + esc(initials(f.name)) + "</div>" +
                    "<div><b>" + esc(f.name) + "</b><small>" + esc(f.email || "No email") + "</small></div></div></td>" +
                "<td>" + esc(locationOf(f)) + "</td>" +
                '<td class="num">' + (Number(f.farm_size) ? esc(f.farm_size) + " ha" : '<span class="muted">-</span>') + "</td>" +
                '<td class="num">' + f.crops.length + "</td>" +
                "<td>" + esc(f.joined ? fmtFull(f.joined) : "-") + "</td>" +
                "<td>" + esc(agoText(f.lastActive)) + "</td>" +
                '<td><span class="pill ' + (isActive ? "active" : "inactive") + '">' + (isActive ? "Active" : "Deactivated") + "</span></td>" +
                '<td class="actions">' +
                    '<button type="button" class="btn small" data-view="' + esc(f.id) + '"><i class="ph-bold ph-eye" aria-hidden="true"></i> View</button>' +
                    '<button type="button" class="btn small ' + (isActive ? "danger" : "") + '" data-toggle="' + esc(f.id) + '">' +
                        (isActive ? "Deactivate" : "Activate") + "</button>" +
                "</td></tr>";
        }).join("");

        footEl.textContent = "Showing " + list.length + " of " + farmers.length + " farmer" + (farmers.length === 1 ? "" : "s") + ".";
        renderPager(pagerEl, list.length, page, PAGE_SIZE, (p) => { page = p; render(); });
    }


    // ---------- actions ----------
    function showDetails(f) {
        document.getElementById("detailsAvatar").textContent = initials(f.name);
        document.getElementById("detailsName").textContent = f.name;
        document.getElementById("detailsEmail").textContent = f.email || "No email on file";

        const rows = [
            ["Status", f.status === "active" ? "Active" : "Deactivated"],
            ["Phone", f.phone || "Not provided"],
            ["Farm location", locationOf(f)],
            ["Location type", f.locationSource === "gps" ? "Exact GPS pin" : f.municipality ? "Town centre" : "Not set"],
            ["Farm size", Number(f.farm_size) ? f.farm_size + " ha" : "Not provided"],
            ["Joined", f.joined ? fmtFull(f.joined) : "Unknown"],
            ["Last active", agoText(f.lastActive)],
            ["Registered crops", String(f.crops.length)]
        ];
        document.getElementById("detailsGrid").innerHTML = rows.map((r) =>
            "<div><dt>" + esc(r[0]) + "</dt><dd>" + esc(r[1]) + "</dd></div>").join("");

        document.getElementById("detailsCrops").innerHTML = f.crops.length
            ? f.crops.map((c) => {
                const state = c.harvestedOn ? "Harvested " + fmtFull(c.harvestedOn) : c.harvest ? "Harvest " + fmtFull(c.harvest) : "";
                return "<li><b>" + esc(c.name || "Crop") + (c.field ? " (" + esc(c.field) + ")" : "") + "</b><span>" +
                    (c.area ? esc(c.area) + " ha" : "") + (c.area && state ? " &middot; " : "") + esc(state) + "</span></li>";
            }).join("")
            : '<li><span style="text-align:left">This farmer has not registered any crops yet.</span></li>';

        detailsDlg.showModal();
    }

    async function toggleStatus(f) {
        const deactivating = f.status === "active";
        const next = deactivating ? "inactive" : "active";

        const yes = await confirmAction(
            (deactivating ? "Deactivate " : "Activate ") + f.name + "?",
            deactivating
                ? "They will not be able to sign in again until you activate the account. Their data is kept."
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
            toast("Could not update. " + (err && err.code === "PERMISSION_DENIED"
                ? "Your database rules do not allow admins to edit users."
                : "Check your connection."));
        }
    }


    // ---------- events ----------
    rowsEl.addEventListener("click", (event) => {
        const viewBtn = event.target.closest("[data-view]");
        const toggleBtn = event.target.closest("[data-toggle]");
        if (viewBtn) {
            const f = farmers.find((x) => x.id === viewBtn.dataset.view);
            if (f) showDetails(f);
        } else if (toggleBtn) {
            const f = farmers.find((x) => x.id === toggleBtn.dataset.toggle);
            if (f) toggleStatus(f);
        }
    });

    [searchInput, statusFilter, townFilter].forEach((el) => {
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