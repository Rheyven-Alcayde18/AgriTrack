<?php
// admin/harvest-management.php
// Every harvest recorded by every farmer: yield, price and revenue, with filters and monthly totals.
// Read-only. Uses admin/includes + assets/css/admin.css. Logic: assets/js/admin-harvests.js

$pageTitle  = 'Harvest management';
$active     = 'harvest-management';
$heading    = 'Harvest management';
$subtitle   = 'Harvest records, yield and revenue across all farmers.';
$topActions = '<button type="button" class="btn" id="refreshBtn"><i class="ph-bold ph-arrow-clockwise" aria-hidden="true"></i> Refresh</button>';
$extraHead  = '<link rel="stylesheet" href="../assets/css/admin-pages.css">';

require __DIR__ . '/includes/header.php';
?>

        <section class="stats" aria-label="Summary">
            <div class="stat"><i class="ph-duotone ph-basket" aria-hidden="true"></i><div><b id="sRecords">0</b><span>Harvest records</span></div></div>
            <div class="stat"><i class="ph-duotone ph-grains" aria-hidden="true"></i><div><b id="sYield">0</b><span>Harvested (tonnes)</span></div></div>
            <div class="stat"><i class="ph-duotone ph-coins" aria-hidden="true"></i><div><b id="sRevenue">0</b><span>Harvest revenue</span></div></div>
            <div class="stat"><i class="ph-duotone ph-tag" aria-hidden="true"></i><div><b id="sPrice">-</b><span>Average price per kg</span></div></div>
            <div class="stat"><i class="ph-duotone ph-hourglass-medium" aria-hidden="true"></i><div><b id="sReady">0</b><span>Ready within 14 days</span></div></div>
        </section>

        <section class="charts" aria-label="Charts">
            <div class="panel"><h2>Harvested per month (tonnes)</h2><div class="cols" id="monthCols"></div></div>
            <div class="panel"><h2>Revenue by crop</h2><div id="cropBars"></div></div>
        </section>

        <div class="toolbar">
            <div class="search">
                <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="searchInput" placeholder="Search by farmer, crop, field or location" aria-label="Search harvests" autocomplete="off">
            </div>
            <select id="townFilter" aria-label="Filter by municipality"><option value="all">All municipalities</option></select>
            <select id="cropFilter" aria-label="Filter by crop"><option value="all">All crops</option></select>
            <label class="fld">From <input type="date" id="dateFrom"></label>
            <label class="fld">To <input type="date" id="dateTo"></label>
            <button type="button" class="btn" id="clearFilters">Clear filters</button>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table class="rt">
                    <thead>
                        <tr>
                            <th>Date</th><th>Farmer</th><th>Location</th><th>Crop</th>
                            <th class="right">Yield (t)</th><th class="right">Price (PHP/kg)</th><th class="right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody id="rows">
                        <tr><td colspan="7"><div class="state"><i class="ph-duotone ph-spinner-gap" aria-hidden="true"></i><b>Loading...</b>Reading harvests from Firebase.</div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="pager" id="pager"></div>
            <div class="foot" id="foot" aria-live="polite"></div>
        </div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<script type="module" src="../assets/js/auth.js"></script>
<script type="module" src="../assets/js/admin-harvests.js"></script>
<script src="../assets/js/chatbot.js"></script>
</body>
</html>
