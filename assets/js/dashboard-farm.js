// assets/js/dashboard-farm.js
// Farmer dashboard data, connected to Firebase Realtime Database.
// Crops (crops/{uid}), harvest history (harvests/{uid}) and the to-do ticks (checklist/{uid}).
// The weather panel is filled by dashboard.js. Loaded by user/dashboard.php.
import {
  requireUser, listCrops, listHarvests, getChecklist, setCheck,
  esc, num, fmtMD, fmtMon, fmtDay, daysText, todayISO, showError
} from "./db.js";

const $ = (id) => document.getElementById(id);
const MON = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function empty(text) {
  return '<div class="ag-empty" style="padding:24px"><p style="margin:0 auto">' + text + "</p></div>";
}

function render(crops, harvests, checks, uid) {
  const open = crops.filter((c) => c.status !== "done");
  const byHarvest = open.slice().sort((a, b) => (a.harvest < b.harvest ? -1 : 1));
  const warn = open.filter((c) => c.status === "warn");

  // Alert
  $("alertBox").innerHTML = warn.length
    ? '<div class="db-alert"><span><b>' + esc(warn[0].name) + " in " + esc(warn[0].field) + " needs water.</b> Soil moisture is " + warn[0].moisture +
      '%, below the healthy range.</span><a class="db-link" href="recommendations.php">See recommendations &rarr;</a></div>'
    : "";

  // Stats
  const area = open.reduce((s, c) => s + c.area, 0);
  const yieldT = open.reduce((s, c) => s + c.yield_t, 0);
  const fields = new Set(open.map((c) => c.field)).size;
  const next = byHarvest[0];
  const tiles = [
    ["Active crops", open.length, warn.length ? warn.length + " need attention" : "All on track"],
    ["Total area", num(area) + " ha", "Across " + fields + " field" + (fields === 1 ? "" : "s")],
    ["Next harvest", next ? fmtMD(next.harvest) : "None", next ? next.name + ", " + next.field : "Add a crop to get started"],
    ["Expected yield", num(yieldT) + " t", "Across " + open.length + " crop" + (open.length === 1 ? "" : "s")],
  ];
  $("stats").innerHTML = tiles.map((t) => '<div class="db-stat"><small>' + esc(t[0]) + "</small><b>" + esc(t[1]) + "</b><span>" + esc(t[2]) + "</span></div>").join("");

  // Crop progress
  $("cropList").innerHTML = open.length
    ? open.map((c) =>
        '<div class="db-crop"><div><b>' + esc(c.name) + "</b><small>" + esc(c.field) + " &middot; " + esc(c.stage) + "</small></div>" +
        '<div class="db-bar" title="' + c.progress + '% grown"><div class="tr"><i style="width:' + c.progress + '%"></i></div><span>' + c.progress + "%</span></div>" +
        '<span class="db-pill ' + c.status + '">' + esc(c.label) + "</span></div>").join("")
    : empty('No crops yet. <a class="db-link" href="crop-monitoring.php">Add your first crop &rarr;</a>');

  // Harvested tons for the last 6 months
  const now = todayISO();
  const months = [];
  let y = Number(now.slice(0, 4)), m = Number(now.slice(5, 7));
  for (let i = 0; i < 6; i++) { months.unshift({ key: y + "-" + String(m).padStart(2, "0"), label: MON[m - 1], v: 0 }); m--; if (m === 0) { m = 12; y--; } }
  harvests.forEach((h) => { const mo = months.find((x) => x.key === h.date.slice(0, 7)); if (mo) mo.v += h.yield_t; });
  const max = Math.max(...months.map((x) => x.v), 0.1);
  const any = months.some((x) => x.v > 0);
  $("yieldChart").innerHTML = months.map((x, i) =>
    '<div class="db-col' + (i === months.length - 1 ? " now" : "") + '"><em>' + (Math.round(x.v * 10) / 10) + '</em><div class="bar" style="height:' + Math.max(3, Math.round((x.v / max) * 150)) + 'px"></div><small>' + x.label + "</small></div>").join("");
  if (!any) $("yieldChart").insertAdjacentHTML("afterend", '<p class="db-note" style="margin-top:10px">No harvests recorded in the last 6 months yet.</p>');

  // Up next
  $("nextList").innerHTML = byHarvest.length
    ? byHarvest.slice(0, 3).map((c) =>
        '<div class="db-next"><div class="db-date"><small>' + fmtMon(c.harvest) + "</small><b>" + fmtDay(c.harvest) + "</b></div>" +
        '<div class="db-next-info"><b>' + esc(c.name) + "</b><small>" + esc(c.field) + " &middot; " + num(c.yield_t) + ' t expected</small></div>' +
        '<span class="db-next-days">' + daysText(c.days) + "</span></div>").join("")
    : empty("No upcoming harvests.");

  // To do (built from the crop records, ticks saved per day)
  const tasks = [];
  warn.forEach((c) => tasks.push({ key: c.id + "-water", text: "Water " + c.field, when: "Today" }));
  byHarvest.filter((c) => c.days <= 14).forEach((c) => tasks.push({ key: c.id + "-transport", text: "Book transport for " + c.name.toLowerCase(), when: daysText(c.days) }));
  byHarvest.filter((c) => c.status === "ok").slice(0, 2).forEach((c) => tasks.push({ key: c.id + "-inspect", text: "Inspect " + c.name.toLowerCase() + " in " + c.field, when: "This week" }));
  const list = tasks.slice(0, 5);
  $("tasks").innerHTML = list.length
    ? list.map((t, i) => {
        const key = "todo-" + now + "-" + t.key;
        return '<li><input type="checkbox" id="t' + i + '" data-key="' + esc(key) + '"' + (checks[key] ? " checked" : "") + '><label for="t' + i + '">' + esc(t.text) + "</label><small>" + esc(t.when) + "</small></li>";
      }).join("")
    : '<li style="color:#6b8a7a">Nothing to do right now.</li>';
  $("tasks").querySelectorAll("input[type=checkbox]").forEach((box) => {
    box.addEventListener("change", async () => {
      try { await setCheck(uid, box.getAttribute("data-key"), box.checked); }
      catch (e) { box.checked = !box.checked; showError("Could not save that tick. " + (e.code || e.message)); }
    });
  });

  // Ticks from earlier days are no longer needed
  Object.keys(checks).filter((k) => k.startsWith("todo-") && k.slice(5, 15) !== now).forEach((k) => setCheck(uid, k, false).catch(() => {}));
}

requireUser().then(async ({ uid }) => {
  try {
    const [crops, harvests, checks] = await Promise.all([listCrops(uid), listHarvests(uid), getChecklist(uid)]);
    render(crops, harvests, checks, uid);
  } catch (e) {
    console.error(e);
    showError("Could not load your farm data. " + (e.code === "PERMISSION_DENIED" ? "Publish database.rules.json in Firebase first." : "Check your connection."));
    $("cropList").innerHTML = "";
  }
});