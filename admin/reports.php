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
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>';

require __DIR__ . '/includes/header.php';
?>

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


    // ---------- the four reports ----------
    // Each builder returns { title, sheet, file, dateNote, headers, rows }.
    // Rows hold real numbers (not text) so Excel can sum and sort them.
    function build(kind) {
        const f = filters();
        const okTown = (m) => f.town === "all" || m === f.town;

        if (kind === "farmers") {
            return {
                title: "Farmers report", sheet: "Farmers", file: "farmers",
                dateNote: "Date range applies to the day the farmer joined.",
                headers: ["Name", "Email", "Phone", "Municipality", "Barangay", "Farm size (ha)", "Status", "Joined", "Last active", "Registered crops"],
                rows: data.farmers
                    .filter((x) => okTown(x.municipality) && inRange(x.joined, f.from, f.to))
                    .map((x) => [
                        x.name, x.email || "", x.phone || "", x.municipality || "", x.barangay || "",
                        r2(x.farm_size), x.status === "active" ? "Active" : "Deactivated",
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


    // ---------- preview ----------
    function show(v) {
        if (typeof v === "number") return v.toLocaleString("en-US", { maximumFractionDigits: 2 });
        return v === null || v === undefined || v === "" ? "-" : String(v);
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

        $("previewBody").innerHTML = rep.rows.slice(0, PREVIEW_ROWS).map((r) =>
            "<tr>" + r.map((v) => '<td class="' + (typeof v === "number" ? "right" : "") + '">' + esc(show(v)) + "</td>").join("") + "</tr>"
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

    ["townFilter", "dateFrom", "dateTo"].forEach((id) => $(id).addEventListener("input", renderPreview));

    $("clearFilters").addEventListener("click", () => {
        $("townFilter").value = "all";
        $("dateFrom").value = "";
        $("dateTo").value = "";
        renderPreview();
    });

    $("downloadCsv").addEventListener("click", downloadCsv);
    $("downloadXlsx").addEventListener("click", downloadXlsx);
    $("downloadAll").addEventListener("click", downloadAll);
    $("refreshBtn").addEventListener("click", load);
    $("logoutBtn").addEventListener("click", signOutAndGo);

    // only admins get past this line
    requireUser({ role: "admin" }).then(load);

</script>

</body>
</html>