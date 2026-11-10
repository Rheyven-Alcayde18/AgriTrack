<?php
// admin/reports.php
// Four reports (farmers, crops, harvests, by municipality) with filters, a preview,
// and downloads as CSV or Excel (.xlsx). Everything is built in the browser from the same
// Firebase data the other admin pages use, so nothing has to be uploaded or generated on the server.

$pageTitle  = 'Reports';
$active     = 'reports';
$heading    = 'Reports';
$subtitle   = 'Pick a report, filter it, preview it, then download it as CSV or Excel.';
$topActions = '<button type="button" class="btn" id="refreshBtn"><i class="ph-bold ph-arrow-clockwise" aria-hidden="true"></i> Refresh</button>' .
              '<button type="button" class="btn primary" id="downloadAll"><i class="ph-bold ph-file-xls" aria-hidden="true"></i> Download all (Excel)</button>';

// SheetJS builds the .xlsx files. It loads from a CDN, so Excel downloads need an internet connection (CSV does not).
$extraHead = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<style>
    /* Report preview table: same look as the Manage farmers table. Scoped to .rp so it only affects this page. */
    .rp .table-wrap { overflow-x: auto; }
    .rp table { width: 100%; border-collapse: collapse; table-layout: auto; }
    .rp th {
        padding: 12px 10px; text-align: left; white-space: nowrap;
        font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #56655b;
        background: #eef7f1; border-bottom: 1px solid #e1eee6;
    }
    .rp td { padding: 12px 10px; font-size: .88rem; border-bottom: 1px solid #eef5f0; vertical-align: middle; color: #1c2a22; white-space: nowrap; }
    .rp td.right, .rp th.right { text-align: right; white-space: nowrap; }
    .rp td:first-child { font-weight: 700; color: #08301d; }
    .rp tbody tr:last-child td { border-bottom: 0; }
    .rp tbody tr:hover { background: #f8fcf9; }
    .rp .fld-search { flex: 1 1 240px; }
    .rp .fld-search input {
        width: 100%; font: inherit; padding: 10px 14px; color: #1c2a22; background: #fff;
        border: 1.5px solid #e1eee6; border-radius: 12px; transition: border-color .2s, box-shadow .2s;
    }
    .rp .fld-search input:focus { outline: 0; border-color: #1a8348; box-shadow: 0 0 0 4px rgba(47, 165, 95, .16); }
    .rp .card { background: #fff; border: 1px solid #e1eee6; border-radius: 18px; overflow: hidden; }
    .rp-person { display: flex; align-items: center; gap: 10px; font-weight: 700; color: #08301d; }
    .rp-avatar {
        flex: none; width: 34px; height: 34px; display: grid; place-items: center; border-radius: 50%;
        font-size: .76rem; font-weight: 800; color: #fff; background: linear-gradient(145deg, #34b974, #0c4a30);
    }
    .rp-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 999px; font-size: .78rem; font-weight: 700; }
    .rp-pill::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .rp-pill.ok { color: #1a8348; background: #e6f5d6; }
    .rp-pill.off { color: #b8402b; background: #fdecea; }
    .rp-pill.warn { color: #9a6a00; background: #fdf5e0; }
    .rp td:first-child { font-weight: 400; }
    .rp .state { padding: 50px 24px; text-align: center; color: #56655b; }
    .rp .state b { display: block; margin-bottom: 4px; font-size: 1rem; color: #08301d; }
    .rp .foot { padding: 12px 16px; font-size: .82rem; color: #56655b; background: #fafdfb; border-top: 1px solid #e1eee6; }
</style>
HTML;

require __DIR__ . '/includes/header.php';
?>

        <div class="rp">

        <!-- OVERALL NUMBERS -->
        <section class="stats" aria-label="Summary">
            <div class="stat"><i class="ph-duotone ph-users-three" aria-hidden="true"></i><div><b id="statFarmers">0</b><span>Farmers</span></div></div>
            <div class="stat"><i class="ph-duotone ph-plant" aria-hidden="true"></i><div><b id="statCrops">0</b><span>Crops</span></div></div>
            <div class="stat"><i class="ph-duotone ph-basket" aria-hidden="true"></i><div><b id="statHarvests">0</b><span>Harvest records</span></div></div>
            <div class="stat"><i class="ph-duotone ph-grains" aria-hidden="true"></i><div><b id="statYield">0</b><span>Harvested (tonnes)</span></div></div>
            <div class="stat"><i class="ph-duotone ph-coins" aria-hidden="true"></i><div><b id="statRevenue">0</b><span>Harvest revenue</span></div></div>
        </section>

        <!-- REPORT TYPE -->
        <div class="tabs" role="tablist" aria-label="Report type">
            <button type="button" class="tab" role="tab" data-report="farmers"   aria-selected="true">Farmers</button>
            <button type="button" class="tab" role="tab" data-report="crops"     aria-selected="false">Crops</button>
            <button type="button" class="tab" role="tab" data-report="harvests"  aria-selected="false">Harvests</button>
            <button type="button" class="tab" role="tab" data-report="towns"     aria-selected="false">By municipality</button>
        </div>

        <!-- FILTERS -->
        <div class="toolbar">
            <label class="fld fld-search">Search
                <input type="search" id="searchInput" placeholder="Search this report" aria-label="Search this report" autocomplete="off">
            </label>
            <label class="fld">Municipality
                <select id="townFilter" aria-label="Filter by municipality">
                    <option value="all">All municipalities</option>
                </select>
            </label>
            <label class="fld" id="fromWrap">From
                <input type="date" id="dateFrom">
            </label>
            <label class="fld" id="toWrap">To
                <input type="date" id="dateTo">
            </label>
            <button type="button" class="btn" id="clearFilters">Clear filters</button>
        </div>

        <!-- PREVIEW + DOWNLOAD -->
        <div class="card">
            <div class="download-bar">
                <div class="meta">
                    <b id="reportTitle">Farmers report</b>
                    <span id="reportMeta">Loading...</span>
                </div>
                <div class="btns">
                    <button type="button" class="btn" id="downloadCsv"><i class="ph-bold ph-file-csv" aria-hidden="true"></i> Download CSV</button>
                    <button type="button" class="btn primary" id="downloadXlsx"><i class="ph-bold ph-file-xls" aria-hidden="true"></i> Download Excel</button>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead id="previewHead"></thead>
                    <tbody id="previewBody">
                        <tr><td><div class="state"><i class="ph-duotone ph-spinner-gap" aria-hidden="true"></i><b>Loading data...</b>Reading records from Firebase.</div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="foot" id="previewFoot" aria-live="polite"></div>
        </div>

        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<script type="module">

    import { requireUser, esc, peso, num, todayISO, signOutAndGo, explain, showError } from "../assets/js/db.js";
    import { loadAdminData, r2, toast } from "../assets/js/admin-data.js";

    const PREVIEW_ROWS = 50;

    const $ = (id) => document.getElementById(id);

    let data = { farmers: [], crops: [], harvests: [] };
    let current = "farmers";


    // ---------- filters ----------
    function filters() {
        return { town: $("townFilter").value, from: $("dateFrom").value, to: $("dateTo").value };
    }

    function inRange(iso, from, to) {
        if (!from && !to) return true;
        if (!iso) return false;
        if (from && iso < from) return false;
        if (to && iso > to) return false;
        return true;
    }


    // Farm size: the number on the farmer's profile if there is one, otherwise the total area of their crops.
    // (The register page does not ask for a farm size, so most profiles have none.)
    function sizeOf(x) {
        if (Number(x.farm_size) > 0) return Number(x.farm_size);
        if (Number(x.cropArea) > 0) return Number(x.cropArea);
        return (x.crops || []).reduce((n, c) => n + (Number(c.area) || 0), 0);
    }


    // ---------- the four reports ----------
    // Each builder returns { title, sheet, file, dateNote, headers, rows }.
    // Rows hold real numbers (not text) so Excel can sum and sort them.
    function buildReport(kind) {
        const f = filters();
        const okTown = (m) => f.town === "all" || m === f.town;

        if (kind === "farmers") {
            return {
                title: "Farmers report", sheet: "Farmers", file: "farmers",
                dateNote: "Date range applies to the day the farmer joined.",
                headers: ["Name", "Email", "Municipality", "Barangay", "Farm size (ha)", "Status", "Joined", "Last active", "Registered crops"],
                rows: data.farmers
                    .filter((x) => okTown(x.municipality) && inRange(x.joined, f.from, f.to))
                    .map((x) => [
                        x.name, x.email || "", x.municipality || "", x.barangay || "",
                        r2(sizeOf(x)), x.status === "active" ? "Active" : "Deactivated",
                        x.joined || "", x.lastActive || "", x.crops.length
                    ])
            };
        }

        if (kind === "crops") {
            return {
                title: "Crops report", sheet: "Crops", file: "crops",
                dateNote: "Date range applies to the planting date.",
                headers: ["Farmer", "Municipality", "Crop", "Field", "Area (ha)", "Planted", "Expected harvest", "Harvested on", "Stage", "Status", "Soil moisture (%)", "Expected yield (t)", "Price (PHP/kg)", "Estimated revenue (PHP)"],
                rows: data.crops
                    .filter((c) => okTown(c.municipality) && inRange(c.planted, f.from, f.to))
                    .map((c) => [
                        c.farmer, c.municipality || "", c.name || "", c.field || "", r2(c.area),
                        c.planted || "", c.harvest || "", c.harvestedOn || "", c.stage, c.label,
                        r2(c.moisture), r2(c.yield_t), r2(c.price), Math.round(c.revenue)
                    ])
            };
        }

        if (kind === "harvests") {
            return {
                title: "Harvests report", sheet: "Harvests", file: "harvests",
                dateNote: "Date range applies to the harvest date.",
                headers: ["Date", "Farmer", "Municipality", "Crop", "Field", "Yield (t)", "Price (PHP/kg)", "Revenue (PHP)"],
                rows: data.harvests
                    .filter((h) => okTown(h.municipality) && inRange(h.date, f.from, f.to))
                    .map((h) => [h.date || "", h.farmer, h.municipality || "", h.crop || "", h.field || "", r2(h.yield_t), r2(h.price), Math.round(h.revenue)])
            };
        }

        // by municipality: one summary row per town (the date range does not apply)
        const towns = {};
        const row = (m) => {
            const key = m || "Not set";
            if (!towns[key]) towns[key] = { farmers: 0, active: 0, crops: 0, area: 0, harvests: 0, yield: 0, revenue: 0 };
            return towns[key];
        };
        data.farmers.filter((x) => okTown(x.municipality)).forEach((x) => {
            const t = row(x.municipality);
            t.farmers++;
            if (x.status === "active") t.active++;
            t.crops += x.crops.length;
            t.area += x.cropArea;
            t.harvests += x.harvests.length;
            t.yield += x.harvestYield;
            t.revenue += x.harvestRevenue;
        });
        return {
            title: "Summary by municipality", sheet: "By municipality", file: "by-municipality",
            dateNote: "The date range does not apply to this summary.",
            headers: ["Municipality", "Farmers", "Active farmers", "Crops", "Crop area (ha)", "Harvest records", "Harvested yield (t)", "Harvest revenue (PHP)"],
            rows: Object.keys(towns).sort().map((name) => {
                const t = towns[name];
                return [name, t.farmers, t.active, t.crops, r2(t.area), t.harvests, r2(t.yield), Math.round(t.revenue)];
            })
        };
    }


    // The search box keeps only rows where any cell contains the typed text (so downloads match what you see).
    function build(kind) {
        const rep = buildReport(kind);
        const q = $("searchInput").value.trim().toLowerCase();
        if (q) rep.rows = rep.rows.filter((r) => r.some((v) => String(v === null || v === undefined ? "" : v).toLowerCase().indexOf(q) >= 0));
        return rep;
    }


    // ---------- preview ----------
    function show(v) {
        if (typeof v === "number") return v.toLocaleString("en-US", { maximumFractionDigits: 2 });
        return v === null || v === undefined || v === "" ? "-" : String(v);
    }

    function initials(name) {
        const parts = String(name || "?").trim().split(/\s+/).slice(0, 2);
        return parts.map((p) => p.charAt(0).toUpperCase()).join("") || "?";
    }

    function renderPreview() {
        const rep = build(current);

        $("reportTitle").textContent = rep.title;
        $("reportMeta").textContent = rep.rows.length + " row" + (rep.rows.length === 1 ? "" : "s") + ". " + rep.dateNote;

        // the date filters only make sense for some reports
        const usesDates = current !== "towns";
        $("fromWrap").style.display = usesDates ? "" : "none";
        $("toWrap").style.display = usesDates ? "" : "none";

        $("previewHead").innerHTML = "<tr>" + rep.headers.map((h, i) => {
            const numeric = rep.rows.length && typeof rep.rows[0][i] === "number";
            return '<th class="' + (numeric ? "right" : "") + '">' + esc(h) + "</th>";
        }).join("") + "</tr>";

        if (!rep.rows.length) {
            $("previewBody").innerHTML = '<tr><td colspan="' + rep.headers.length + '"><div class="state"><i class="ph-duotone ph-file-magnifying-glass" aria-hidden="true"></i><b>No rows to show</b>Nothing matches these filters.</div></td></tr>';
            $("previewFoot").textContent = "";
            return;
        }

        // Preview only: the first column of the farmer, crop and harvest reports gets an avatar,
        // and Status columns become coloured pills. The CSV and Excel downloads stay plain values.
        const hasPerson = rep.headers[0] === "Name" || rep.headers[0] === "Farmer";
        const statusCol = rep.headers.indexOf("Status");
        const pillClass = (v) => /^(active|on track)$/i.test(v) ? "ok" : /deactivated/i.test(v) ? "off" : "warn";

        $("previewBody").innerHTML = rep.rows.slice(0, PREVIEW_ROWS).map((r) =>
            "<tr>" + r.map((v, i) => {
                const text = esc(show(v));
                if (i === 0 && hasPerson) {
                    return '<td><div class="rp-person"><span class="rp-avatar" aria-hidden="true">' + esc(initials(v)) + "</span>" + text + "</div></td>";
                }
                if (i === statusCol && v) return '<td><span class="rp-pill ' + pillClass(String(v)) + '">' + text + "</span></td>";
                return '<td class="' + (typeof v === "number" ? "right" : "") + '">' + text + "</td>";
            }).join("") + "</tr>"
        ).join("");

        $("previewFoot").textContent = rep.rows.length > PREVIEW_ROWS
            ? "Showing the first " + PREVIEW_ROWS + " of " + rep.rows.length + " rows. The download contains all " + rep.rows.length + "."
            : "Showing all " + rep.rows.length + " row" + (rep.rows.length === 1 ? "" : "s") + ".";
    }


    // ---------- downloads ----------
    function stamp() { return todayISO(); }

    function saveBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1500);
    }

    // one CSV cell: quotes where needed, and a leading ' so Excel never runs text as a formula
    function csvCell(v) {
        if (v === null || v === undefined) return "";
        let s = String(v);
        if (typeof v !== "number" && /^[=+\-@\t\r]/.test(s)) s = "'" + s;
        if (/[",\r\n]/.test(s)) s = '"' + s.replace(/"/g, '""') + '"';
        return s;
    }

    function toCsv(rep) {
        const lines = [rep.headers].concat(rep.rows).map((r) => r.map(csvCell).join(","));
        // the BOM makes Excel read the file as UTF-8 (so Biñan and Los Baños stay correct)
        return "\uFEFF" + lines.join("\r\n");
    }

    function downloadCsv() {
        const rep = build(current);
        if (!rep.rows.length) { toast("Nothing to download with these filters."); return; }
        saveBlob(new Blob([toCsv(rep)], { type: "text/csv;charset=utf-8" }), "agritrack-" + rep.file + "-" + stamp() + ".csv");
        toast("CSV downloaded (" + rep.rows.length + " rows).");
    }

    function addSheet(wb, rep) {
        const ws = XLSX.utils.aoa_to_sheet([rep.headers].concat(rep.rows));
        // widen each column to fit its longest value (checked on the first 200 rows)
        ws["!cols"] = rep.headers.map((h, i) => {
            let w = h.length;
            rep.rows.slice(0, 200).forEach((r) => { w = Math.max(w, String(r[i] === null || r[i] === undefined ? "" : r[i]).length); });
            return { wch: Math.min(42, w + 2) };
        });
        XLSX.utils.book_append_sheet(wb, ws, rep.sheet);
    }

    function excelReady() {
        if (typeof XLSX === "undefined") {
            toast("The Excel library could not load. Check your internet connection, or use CSV.");
            return false;
        }
        return true;
    }

    function downloadXlsx() {
        if (!excelReady()) return;
        const rep = build(current);
        if (!rep.rows.length) { toast("Nothing to download with these filters."); return; }
        const wb = XLSX.utils.book_new();
        addSheet(wb, rep);
        XLSX.writeFile(wb, "agritrack-" + rep.file + "-" + stamp() + ".xlsx");
        toast("Excel file downloaded (" + rep.rows.length + " rows).");
    }

    function downloadAll() {
        if (!excelReady()) return;
        const wb = XLSX.utils.book_new();
        ["farmers", "crops", "harvests", "towns"].forEach((k) => addSheet(wb, build(k)));
        XLSX.writeFile(wb, "agritrack-all-reports-" + stamp() + ".xlsx");
        toast("Workbook downloaded with 4 sheets.");
    }


    // ---------- load ----------
    function fillTowns() {
        const select = $("townFilter");
        const keep = select.value;
        const towns = Array.from(new Set(data.farmers.map((f) => f.municipality).filter(Boolean))).sort();
        select.innerHTML = '<option value="all">All municipalities</option>' +
            towns.map((t) => '<option value="' + esc(t) + '">' + esc(t) + "</option>").join("");
        if (towns.indexOf(keep) >= 0) select.value = keep;
    }

    function drawStats() {
        $("statFarmers").textContent = data.farmers.length;
        $("statCrops").textContent = data.crops.length;
        $("statHarvests").textContent = data.harvests.length;
        $("statYield").textContent = num(data.harvests.reduce((n, h) => n + h.yield_t, 0));
        $("statRevenue").textContent = peso(data.harvests.reduce((n, h) => n + h.revenue, 0));
    }

    async function load() {
        $("refreshBtn").disabled = true;
        try {
            data = await loadAdminData();
            drawStats();
            fillTowns();
            renderPreview();
        } catch (err) {
            console.error(err);
            showError(explain(err, "Could not load the report data."));
            $("reportMeta").textContent = "Could not load data.";
            $("previewBody").innerHTML = '<tr><td><div class="state"><i class="ph-duotone ph-warning-circle" aria-hidden="true"></i><b>Could not load the data</b>Check the message above and your connection, then press Refresh.</div></td></tr>';
        } finally {
            $("refreshBtn").disabled = false;
        }
    }


    // ---------- events ----------
    document.querySelector(".tabs").addEventListener("click", (event) => {
        const tab = event.target.closest("[data-report]");
        if (!tab) return;
        current = tab.dataset.report;
        document.querySelectorAll(".tab").forEach((t) => t.setAttribute("aria-selected", t === tab ? "true" : "false"));
        renderPreview();
    });

    ["searchInput", "townFilter", "dateFrom", "dateTo"].forEach((id) => $(id).addEventListener("input", renderPreview));

    $("clearFilters").addEventListener("click", () => {
        $("searchInput").value = "";
        $("townFilter").value = "all";
        $("dateFrom").value = "";
        $("dateTo").value = "";
        renderPreview();
    });

    $("downloadCsv").addEventListener("click", downloadCsv);
    $("downloadXlsx").addEventListener("click", downloadXlsx);
    $("downloadAll").addEventListener("click", downloadAll);
    $("refreshBtn").addEventListener("click", load);
    if ($("logoutBtn")) $("logoutBtn").addEventListener("click", signOutAndGo);

    // only admins get past this line
    requireUser({ role: "admin" }).then(load);

</script>

</body>
</html>