// assets/js/crops.js
// Crop monitoring, connected to Firebase Realtime Database (crops/{uid}).
// Add, edit and delete crops, log soil moisture and record a harvest.
// Loaded by user/crop-monitoring.php.
import {
  requireUser, listCrops, saveCrop, deleteCrop, logMoisture, recordHarvest, seedMyFarm,
  esc, num, fmtMD, daysText, STAGES, CROP_DEFAULTS, todayISO, addDays, toast, showError
} from "./db.js";

const $ = (id) => document.getElementById(id);
const CROP_ICON = '<path d="M7 20h10"/><path d="M12 20v-9"/><path d="M12 11c0-3 2-5 6-5 0 4-2 6-6 5Z"/><path d="M12 13c0-2.5-1.8-4.5-5.5-4.5 0 3.5 2 5 5.5 4.5Z"/>';

let uid = null;
let crops = [];
let filter = "all";

/* ---------- render ---------- */
function renderStats() {
  const live = crops.filter((c) => c.status !== "done");
  const area = live.reduce((s, c) => s + c.area, 0);
  const fields = new Set(live.map((c) => c.field)).size;
  const soon = live.filter((c) => c.status === "soon").length;
  const warn = live.filter((c) => c.status === "warn").length;
  const tiles = [
    ["Crops monitored", live.length, "Across " + fields + " field" + (fields === 1 ? "" : "s"), ""],
    ["Total area", num(area) + " ha", "Planted this season", ""],
    ["Harvest soon", soon, "Ready within two weeks", ""],
    ["Need attention", warn, warn ? "Check the flagged crops" : "Everything looks fine", warn ? "warn" : ""],
  ];
  $("stats").innerHTML = tiles.map((t) =>
    '<div class="cm-stat ' + t[3] + '"><small>' + esc(t[0]) + "</small><b>" + esc(t[1]) + "</b><span>" + esc(t[2]) + "</span></div>").join("");
}

function card(c) {
  const steps = STAGES.map((s, i) => '<div class="cm-step ' + (i < c.stageIndex ? "done" : i === c.stageIndex ? "now" : "") + '">' + s + "</div>").join("");
  const open = c.status !== "done";
  return '<article class="cm-card" id="crop-' + esc(c.id) + '" data-status="' + c.status + '">' +
    '<div class="cm-top"><div class="cm-name"><span class="cm-badge"><svg viewBox="0 0 24 24" aria-hidden="true">' + CROP_ICON + "</svg></span>" +
    "<div><b>" + esc(c.name) + "</b><small>" + esc(c.field) + " &middot; " + num(c.area) + " ha</small></div></div>" +
    '<span class="cm-pill ' + c.status + '">' + esc(c.label) + "</span></div>" +
    '<div class="cm-steps" aria-label="Growth stage: ' + esc(c.stage) + '">' + steps + "</div>" +
    '<div class="cm-prog" title="' + c.progress + '% grown"><div class="cm-bar-track"><i style="width:' + c.progress + '%"></i></div><b>' + c.progress + "%</b></div>" +
    '<div class="cm-facts">' +
    '<div class="cm-fact"><small>Planted</small><b>' + fmtMD(c.planted) + "</b><span>" + c.planted.slice(0, 4) + "</span></div>" +
    '<div class="cm-fact"><small>Harvest</small><b>' + fmtMD(c.harvest) + "</b><span>" + (open ? daysText(c.days) : "Done") + "</span></div>" +
    '<div class="cm-fact"><small>Soil moisture</small><b>' + c.moisture + '%</b><div class="cm-moist ' + (c.moistureLow ? "low" : "") + '"><i style="width:' + Math.min(100, c.moisture) + '%"></i></div></div>' +
    "</div>" +
    '<p class="cm-action"><b>Next:</b> ' + esc(c.action) + "</p>" +
    '<div class="ag-actions">' +
    (open ? '<button class="ag-ghost" data-act="moisture" data-id="' + esc(c.id) + '">Log moisture</button>' +
            '<button class="ag-ghost" data-act="harvest" data-id="' + esc(c.id) + '">Record harvest</button>' : "") +
    '<button class="ag-ghost" data-act="edit" data-id="' + esc(c.id) + '">Edit</button>' +
    '<button class="ag-ghost danger" data-act="delete" data-id="' + esc(c.id) + '">Delete</button>' +
    "</div></article>";
}

function renderGrid() {
  const grid = $("cropGrid");
  if (!crops.length) {
    grid.innerHTML = '<div class="ag-empty" style="grid-column:1/-1"><b>No crops yet</b><p>Add the crops you are growing and AgriTrack will track their growth stage, harvest date and soil moisture.</p>' +
      '<div class="ag-actions"><button class="ag-btn" data-act="add">Add your first crop</button><button class="ag-ghost" data-act="demo">Load demo farm</button></div></div>';
    $("empty").style.display = "none";
    return;
  }
  const list = crops.slice().sort((a, b) => (a.harvest < b.harvest ? -1 : 1));
  grid.innerHTML = list.map(card).join("");
  applyFilter();
}

function applyFilter() {
  let shown = 0;
  document.querySelectorAll(".cm-card").forEach((el) => {
    const match = filter === "all" || el.getAttribute("data-status") === filter;
    el.hidden = !match;
    if (match) shown++;
  });
  $("empty").style.display = shown || !crops.length ? "none" : "block";
}

async function refresh() {
  crops = await listCrops(uid);
  renderStats();
  renderGrid();
  if (location.hash) { const t = document.querySelector(location.hash); if (t) t.scrollIntoView({ block: "center" }); }
}

/* ---------- dialogs ---------- */
function openDlg(dlg) { if (dlg.showModal) dlg.showModal(); else dlg.setAttribute("open", ""); }
function closeDlg(dlg) { if (dlg.close) dlg.close(); else dlg.removeAttribute("open"); }
document.querySelectorAll("dialog [data-close]").forEach((b) => b.addEventListener("click", () => closeDlg(b.closest("dialog"))));

let editing = null;
function openCrop(c) {
  editing = c || null;
  const f = $("cropForm");
  f.reset();
  $("cropErr").textContent = "";
  $("cropTitle").textContent = c ? "Edit " + c.name : "Add crop";
  const t = todayISO();
  f.name.value = c ? c.name : "";
  f.field.value = c ? c.field : "";
  f.area.value = c ? c.area : "";
  f.moisture.value = c ? c.moisture : 60;
  f.planted.value = c ? c.planted : t;
  f.harvest.value = c ? c.harvest : "";
  f.yield_t.value = c ? c.yield_t : "";
  f.price.value = c ? c.price : "";
  openDlg($("cropDlg"));
  f.name.focus();
}

// Fill in sensible numbers when the farmer picks a known crop (only into empty boxes)
function suggest() {
  if (editing) return;
  const f = $("cropForm");
  const d = CROP_DEFAULTS[f.name.value.trim()];
  if (!d) return;
  if (f.planted.value && !f.harvest.value) f.harvest.value = addDays(f.planted.value, d.days);
  if (!f.price.value) f.price.value = d.price;
  const a = Number(f.area.value);
  if (a > 0 && !f.yield_t.value) f.yield_t.value = Math.round(a * d.yield_per_ha * 10) / 10;
}
["name", "area", "planted"].forEach((n) => $("cropForm")[n].addEventListener("change", suggest));

$("cropForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const f = e.target, err = $("cropErr");
  const name = f.name.value.trim(), field = f.field.value.trim();
  const area = Number(f.area.value), moisture = Number(f.moisture.value);
  if (!name || !field) return (err.textContent = "Enter the crop and the field.");
  if (!(area > 0)) return (err.textContent = "Area must be more than 0 ha.");
  if (!f.planted.value || !f.harvest.value) return (err.textContent = "Enter the planting and harvest dates.");
  if (f.harvest.value <= f.planted.value) return (err.textContent = "Harvest date must be after the planting date.");
  if (!(moisture >= 0 && moisture <= 100)) return (err.textContent = "Soil moisture must be between 0 and 100.");
  const rec = {
    id: editing ? editing.id : undefined, name, field, area, moisture,
    planted: f.planted.value, harvest: f.harvest.value,
    yield_t: f.yield_t.value === "" ? 0 : Number(f.yield_t.value),
    price: f.price.value === "" ? 0 : Number(f.price.value),
    harvestedOn: editing ? editing.harvestedOn : undefined,
  };
  const btn = $("cropSave"); btn.disabled = true;
  try { await saveCrop(uid, rec); closeDlg($("cropDlg")); toast(editing ? "Crop updated" : "Crop added"); await refresh(); }
  catch (ex) { console.error(ex); err.textContent = "Could not save. " + (ex.code || ex.message); }
  btn.disabled = false;
});

let target = null;
$("moistForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const v = Number(e.target.moisture.value);
  if (e.target.moisture.value === "" || !(v >= 0 && v <= 100)) return ($("moistErr").textContent = "Enter a number from 0 to 100.");
  try { await logMoisture(uid, target, v); closeDlg($("moistDlg")); toast("Reading saved"); await refresh(); }
  catch (ex) { $("moistErr").textContent = "Could not save. " + (ex.code || ex.message); }
});

$("harvForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const f = e.target;
  if (!f.date.value || f.yield_t.value === "" || f.price.value === "") return ($("harvErr").textContent = "Fill in all three fields.");
  try {
    await recordHarvest(uid, target, { date: f.date.value, yield_t: f.yield_t.value, price: f.price.value });
    closeDlg($("harvDlg")); toast("Harvest recorded"); await refresh();
  } catch (ex) { $("harvErr").textContent = "Could not save. " + (ex.code || ex.message); }
});

/* ---------- clicks ---------- */
$("addCrop").addEventListener("click", () => openCrop(null));
document.querySelectorAll(".cm-filter").forEach((btn) => btn.addEventListener("click", () => {
  filter = btn.getAttribute("data-filter");
  document.querySelectorAll(".cm-filter").forEach((b) => b.classList.toggle("active", b === btn));
  applyFilter();
}));

$("cropGrid").addEventListener("click", async (e) => {
  const b = e.target.closest("[data-act]");
  if (!b) return;
  const act = b.getAttribute("data-act");
  if (act === "add") return openCrop(null);
  if (act === "demo") {
    b.disabled = true;
    try { await seedMyFarm(uid); toast("Demo farm added"); await refresh(); } catch (ex) { showError("Could not load the demo farm. " + (ex.code || ex.message)); b.disabled = false; }
    return;
  }
  const c = crops.find((x) => x.id === b.getAttribute("data-id"));
  if (!c) return;
  target = c;
  if (act === "edit") openCrop(c);
  if (act === "moisture") {
    $("moistTitle").textContent = "Log soil moisture: " + c.name + " (" + c.field + ")";
    $("moistForm").moisture.value = c.moisture; $("moistErr").textContent = "";
    openDlg($("moistDlg"));
  }
  if (act === "harvest") {
    $("harvTitle").textContent = "Record harvest: " + c.name + " (" + c.field + ")";
    const f = $("harvForm");
    f.date.value = todayISO(); f.yield_t.value = c.yield_t || ""; f.price.value = c.price || ""; $("harvErr").textContent = "";
    openDlg($("harvDlg"));
  }
  if (act === "delete" && confirm("Delete " + c.name + " (" + c.field + ")? This cannot be undone.")) {
    try { await deleteCrop(uid, c); toast("Crop deleted"); await refresh(); } catch (ex) { showError("Could not delete. " + (ex.code || ex.message)); }
  }
});

/* ---------- start ---------- */
requireUser().then(async (s) => {
  uid = s.uid;
  try { await refresh(); } catch (ex) { console.error(ex); showError("Could not load crops. " + (ex.code === "PERMISSION_DENIED" ? "Publish database.rules.json in Firebase first." : "Check your connection.")); $("cropGrid").innerHTML = ""; }
});