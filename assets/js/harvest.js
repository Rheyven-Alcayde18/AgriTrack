// assets/js/harvest.js
// Harvest management, connected to Firebase Realtime Database.
// Crops come from crops/{uid} (same records as Crop monitoring), history from harvests/{uid},
// and the preparation ticks are saved in checklist/{uid}.
// Loaded by user/harvest-management.php.
import {
  requireUser, listCrops, listHarvests, getChecklist, setCheck,
  esc, num, peso, fmtMD, fmtMon, fmtDay, fmtFull, daysText, todayISO, showError
} from "./db.js";

const $ = (id) => document.getElementById(id);

function stats(open) {
  const next = open[0];
  const yieldT = open.reduce((s, c) => s + c.yield_t, 0);
  const rev = open.reduce((s, c) => s + c.revenue, 0);
  const soon = open.filter((c) => c.days <= 14).length;
  const tiles = [
    ["Next harvest", next ? next.name : "None", next ? fmtMD(next.harvest) + " · " + daysText(next.days) : "Add a crop in Crop monitoring"],
    ["Expected yield", num(yieldT) + " t", "Across " + open.length + " crop" + (open.length === 1 ? "" : "s")],
    ["Estimated revenue", peso(rev), "At current expected prices"],
    ["Ready within 2 weeks", soon, soon ? "Start preparing now" : "Nothing urgent"],
  ];
  $("stats").innerHTML = tiles.map((t) => '<div class="hm-stat"><small>' + esc(t[0]) + "</small><b>" + esc(t[1]) + "</b><span>" + esc(t[2]) + "</span></div>").join("");
}

function timeline(open) {
  const maxDays = Math.max(1, ...open.map((c) => c.days)) + 6;
  let html = '<a class="hm-mark today" style="left:0%" href="#"><i></i><span><b>Today</b><small>' + fmtMD(todayISO()) + "</small></span></a>";
  open.forEach((c, i) => {
    const left = Math.round((Math.max(0, c.days) / maxDays) * 1000) / 10;
    html += '<a class="hm-mark ' + c.hs + (i % 2 === 0 ? " up" : "") + '" style="left:' + left + '%" href="#crop-' + esc(c.id) + '">' +
      "<i></i><span><b>" + esc(c.name) + "</b><small>" + fmtMD(c.harvest) + "</small></span></a>";
  });
  $("track").innerHTML = html;
}

function schedule(open) {
  if (!open.length) {
    $("sched").innerHTML = '<div class="ag-empty"><b>No upcoming harvests</b><p>Crops you add in Crop monitoring appear here with their expected harvest date.</p><div class="ag-actions"><a class="ag-btn" href="crop-monitoring.php" style="text-decoration:none">Go to Crop monitoring</a></div></div>';
    return;
  }
  $("sched").innerHTML = open.map((c) =>
    '<div class="hm-row" id="crop-' + esc(c.id) + '">' +
    '<div class="hm-date"><small>' + fmtMon(c.harvest) + "</small><b>" + fmtDay(c.harvest) + "</b></div>" +
    '<div class="hm-info"><b>' + esc(c.name) + "</b><small>" + esc(c.field) + " &middot; " + num(c.area) + " ha &middot; " + num(c.yield_t) + " t expected</small>" +
    '<div class="hm-ready" title="Growth progress from Crop monitoring"><div class="tr"><i style="width:' + c.progress + '%"></i></div><span>' + esc(c.stage) + " &middot; " + c.progress + "%</span></div></div>" +
    '<div class="hm-side"><span class="hm-pill ' + c.hs + '">' + esc(c.hl) + "</span><b>" + peso(c.revenue) + "</b><small>" + daysText(c.days) + "</small>" +
    '<a class="hm-link" href="crop-monitoring.php#crop-' + esc(c.id) + '">View in crop monitoring &rarr;</a>' +
    '<a class="hm-link" href="market-locations.php?crop=' + encodeURIComponent(c.name.toLowerCase()) + '">Find best market &rarr;</a></div></div>').join("");
}

function prep(open, checks, uid) {
  if (!open.length) { $("prep").innerHTML = '<p class="hm-note">Nothing to prepare yet.</p>'; return; }
  $("prep").innerHTML = open.slice(0, 3).map((c) =>
    '<div class="hm-group"><h3>' + esc(c.name) + " <small>" + fmtMD(c.harvest) + "</small></h3>" +
    c.prep.map((task, i) => {
      const key = "hm-" + c.id + "-" + i;
      return '<label><input type="checkbox" data-key="' + esc(key) + '"' + (checks[key] ? " checked" : "") + "><span>" + esc(task) + "</span></label>";
    }).join("") + "</div>").join("");
  $("prep").querySelectorAll("input[type=checkbox]").forEach((box) => {
    box.addEventListener("change", async () => {
      try { await setCheck(uid, box.getAttribute("data-key"), box.checked); }
      catch (e) { box.checked = !box.checked; showError("Could not save that tick. " + (e.code || e.message)); }
    });
  });
}

function past(rows) {
  if (!rows.length) {
    $("past").innerHTML = '<tr><td colspan="6" style="color:#6b8a7a">No harvests recorded yet. Use &ldquo;Record harvest&rdquo; on a crop in Crop monitoring.</td></tr>';
    $("pastFoot").innerHTML = "";
    return;
  }
  let y = 0, rev = 0;
  $("past").innerHTML = rows.map((p) => {
    const r = p.yield_t * 1000 * p.price; y += p.yield_t; rev += r;
    return "<tr><td>" + fmtFull(p.date) + "</td><td><b>" + esc(p.crop) + "</b></td><td>" + esc(p.field || "") + '</td><td class="r">' + num(p.yield_t) +
      ' t</td><td class="r">' + peso(p.price) + '/kg</td><td class="r">' + peso(r) + "</td></tr>";
  }).join("");
  $("pastFoot").innerHTML = '<tr><td colspan="3">Total recorded</td><td class="r">' + num(y) + ' t</td><td></td><td class="r">' + peso(rev) + "</td></tr>";
}

requireUser().then(async ({ uid }) => {
  try {
    const [crops, history, checks] = await Promise.all([listCrops(uid), listHarvests(uid), getChecklist(uid)]);
    const open = crops.filter((c) => c.status !== "done").sort((a, b) => (a.harvest < b.harvest ? -1 : 1));
    stats(open); timeline(open); schedule(open); prep(open, checks, uid); past(history);
    if (location.hash) { const t = document.querySelector(location.hash); if (t) t.scrollIntoView({ block: "center" }); }
  } catch (e) {
    console.error(e);
    showError("Could not load harvest data. " + (e.code === "PERMISSION_DENIED" ? "Publish database.rules.json in Firebase first." : "Check your connection."));
    $("sched").innerHTML = "";
  }
});