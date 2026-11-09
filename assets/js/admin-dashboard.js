// assets/js/admin-dashboard.js
// Admin dashboard, connected to Firebase Realtime Database.
// Reads every farmer, crop, harvest and activity record (admins only, see database.rules.json).
// Loaded by admin/dashboard.php.
import { requireUser, listAllUsers, listAll, derive, esc, num, peso, todayISO, fmtMD, showError } from "./db.js";

const $ = (id) => document.getElementById(id);
const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

const none = (text) => '<p class="ad-none">' + esc(text) + "</p>";
const bars = (items, cls) => {
  const max = Math.max(...items.map((i) => i.value), 1);
  return items.map((i) =>
    '<div><div class="ad-bar-top"><b>' + esc(i.label) + "</b><span>" + esc(i.note) + '</span></div><div class="ad-track ' + (cls || "") + '"><i style="width:' + Math.round((i.value / max) * 100) + '%"></i></div></div>').join("");
};

requireUser({ role: "admin" }).then(async () => {
  try {
    const [users, cropsByUid, harvestsByUid, actsByUid] = await Promise.all([
      listAllUsers(), listAll("crops"), listAll("harvests"), listAll("activities"),
    ]);

    const farmers = users.filter((u) => u.role !== "admin");
    const nameOf = {};
    farmers.forEach((f) => { nameOf[f.id] = f.name || f.email || "Farmer"; });

    // Crops (only for farmers that exist)
    const crops = [];
    Object.keys(cropsByUid).forEach((uid) => {
      if (!nameOf[uid]) return;
      Object.entries(cropsByUid[uid] || {}).forEach(([id, v]) => {
        try { crops.push({ ...derive({ ...v, id }), uid, farmer: nameOf[uid] }); } catch (e) { /* skip a malformed record */ }
      });
    });
    const live = crops.filter((c) => c.status !== "done");

    // Harvests
    const harvests = [];
    Object.keys(harvestsByUid).forEach((uid) => {
      if (!nameOf[uid]) return;
      Object.values(harvestsByUid[uid] || {}).forEach((h) => { if (h && h.date) harvests.push(h); });
    });

    $("sampleNote").style.display = farmers.some((f) => f.sample) ? "block" : "none";
    $("empty").style.display = farmers.length ? "none" : "block";

    // ---- stats
    const active = farmers.filter((f) => f.status !== "inactive").length;
    const area = live.reduce((s, c) => s + c.area, 0);
    const tons = harvests.reduce((s, h) => s + (Number(h.yield_t) || 0), 0);
    const revenue = harvests.reduce((s, h) => s + (Number(h.yield_t) || 0) * 1000 * (Number(h.price) || 0), 0);
    const warn = live.filter((c) => c.status === "warn").length;
    const tiles = [
      ["Farmers", farmers.length, active + " active, " + (farmers.length - active) + " inactive", ""],
      ["Crops growing", live.length, num(area) + " ha planted", ""],
      ["Total harvested", num(tons) + " t", peso(revenue) + " estimated sales", ""],
      ["Need attention", warn, warn ? "Crops with dry soil" : "Everything looks fine", warn ? "warn" : ""],
    ];
    $("stats").innerHTML = tiles.map((t) =>
      '<div class="ad-stat ' + t[3] + '"><small>' + esc(t[0]) + "</small><b>" + esc(t[1]) + "</b><span>" + esc(t[2]) + "</span></div>").join("");

    // ---- harvested per month (last 6 months)
    const [ty, tm] = todayISO().split("-").map(Number);
    const months = [];
    for (let i = 5; i >= 0; i--) {
      const d = new Date(Date.UTC(ty, tm - 1 - i, 1));
      months.push({ key: d.getUTCFullYear() + "-" + String(d.getUTCMonth() + 1).padStart(2, "0"), label: MONTHS[d.getUTCMonth()], t: 0 });
    }
    harvests.forEach((h) => { const m = months.find((x) => x.key === h.date.slice(0, 7)); if (m) m.t += Number(h.yield_t) || 0; });
    const top = Math.max(...months.map((m) => m.t), 1);
    $("chart").innerHTML = months.map((m) =>
      '<div class="ad-col' + (m.t ? "" : " zero") + '"><b>' + (m.t ? num(m.t) : "0") + '</b><i style="height:' + Math.max(3, Math.round((m.t / top) * 130)) + 'px"></i><small>' + m.label + "</small></div>").join("");

    // ---- recently joined
    const joined = farmers.slice().sort((a, b) => ((a.joined || "") < (b.joined || "") ? 1 : -1)).slice(0, 5);
    $("joined").innerHTML = joined.length ? joined.map((f) =>
      '<div class="ad-row"><div><b>' + esc(f.name || f.email) + "</b><small>" + esc(f.municipality || "No municipality") + (f.joined ? " &middot; joined " + esc(fmtMD(f.joined)) : "") +
      '</small></div><span class="ad-pill ' + (f.status === "inactive" ? "inactive" : "active") + '">' + (f.status === "inactive" ? "Inactive" : "Active") + "</span></div>").join("") : none("No farmers yet.");

    // ---- farmers by municipality
    const towns = {};
    farmers.forEach((f) => { const t = f.municipality || "Not set"; towns[t] = (towns[t] || 0) + 1; });
    const townList = Object.entries(towns).sort((a, b) => b[1] - a[1]).slice(0, 6)
      .map(([label, value]) => ({ label, value, note: value + " farmer" + (value === 1 ? "" : "s") }));
    $("towns").innerHTML = townList.length ? bars(townList) : none("No farmers yet.");

    // ---- crop mix
    const mix = {};
    live.forEach((c) => { const k = c.name || "Other"; mix[k] = mix[k] || { area: 0, n: 0 }; mix[k].area += c.area; mix[k].n += 1; });
    const mixList = Object.entries(mix).sort((a, b) => b[1].area - a[1].area).slice(0, 6)
      .map(([label, v]) => ({ label, value: v.area, note: num(v.area) + " ha, " + v.n + " crop" + (v.n === 1 ? "" : "s") }));
    $("mix").innerHTML = mixList.length ? bars(mixList, "blue") : none("No crops added yet.");

    // ---- needs attention
    const attn = live.filter((c) => c.status === "warn" || c.status === "soon")
      .sort((a, b) => (a.status === b.status ? a.days - b.days : a.status === "warn" ? -1 : 1)).slice(0, 6);
    $("attention").innerHTML = attn.length ? attn.map((c) =>
      '<div class="ad-row"><div><b>' + esc(c.name) + " &middot; " + esc(c.field) + "</b><small>" + esc(c.farmer) + " &middot; " +
      (c.status === "warn" ? "soil moisture " + c.moisture + "%" : "harvest " + esc(fmtMD(c.harvest))) +
      '</small></div><span class="ad-pill ' + c.status + '">' + esc(c.label) + "</span></div>").join("") : none("Nothing needs attention.");

    // ---- recent activity
    const acts = [];
    Object.keys(actsByUid).forEach((uid) => {
      if (!nameOf[uid]) return;
      Object.values(actsByUid[uid] || {}).forEach((a) => { if (a && a.text) acts.push({ ...a, farmer: nameOf[uid] }); });
    });
    acts.sort((a, b) => (b.ts || 0) - (a.ts || 0));
    $("activity").innerHTML = acts.length ? acts.slice(0, 7).map((a) =>
      '<div class="ad-row"><div><b>' + esc(a.farmer) + "</b><small>" + esc(a.text) + "</small></div><span>" + esc(a.date ? fmtMD(a.date) : "") + "</span></div>").join("") : none("No activity yet.");
  } catch (err) {
    console.error(err);
    showError("Could not load the admin data. " + (err && err.code === "PERMISSION_DENIED" ? "Publish database.rules.json in Firebase, and make sure your account's role is admin." : "Check your connection and reload."));
  }
});