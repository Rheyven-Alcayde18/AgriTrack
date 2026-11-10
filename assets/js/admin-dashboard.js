// assets/js/admin-dashboard.js
// Admin dashboard, read from Firebase Realtime Database (users, crops, harvests, activities).
// Only an account whose users/{uid}/role is "admin" can open it. Loaded by admin/dashboard.php.
import {
  requireUser, listAllUsers, listAll, derive, toList, seedSample,
  esc, num, peso, todayISO, dayDiff, locationText, showError, explain
} from "./db.js";

const $ = (id) => document.getElementById(id);
const MON = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
const none = (t) => '<p class="ad-none">' + esc(t) + "</p>";
const plural = (n, w) => n + " " + w + (n === 1 ? "" : "s");

function ago(iso) {
  const d = dayDiff(iso, todayISO());
  if (d <= 0) return "Today";
  if (d === 1) return "Yesterday";
  if (d < 30) return d + " days ago";
  return Math.round(d / 30) + " mo ago";
}

function bars(rows, cls) {
  if (!rows.length) return none("Nothing to show yet.");
  const max = Math.max(...rows.map((r) => r.value), 1);
  return rows.map((r) =>
    '<div><div class="ad-bar-top"><b>' + esc(r.label) + "</b><span>" + esc(r.text) + '</span></div><div class="ad-track ' + (cls || "") + '"><i style="width:' + Math.max(3, Math.round((r.value / max) * 100)) + '%"></i></div></div>').join("");
}

// Pins: GPS pins are exact; town-centre pins get a tiny fixed offset so farms in the same town do not sit on top of each other.
function jitter(id, n) { let h = 0; for (let i = 0; i < id.length; i++) h = (h * 31 + id.charCodeAt(i)) | 0; return (((h >>> (n * 8)) & 255) / 255 - 0.5) * 0.012; }

function drawMap(farmers) {
  const located = farmers.filter((f) => f.lat && f.lon);
  if (!located.length || typeof L === "undefined") {
    $("farmMap").style.display = "none";
    $("mapNone").style.display = "block";
    if (typeof L === "undefined" && located.length) $("mapNone").textContent = "The map could not load. Check your connection.";
    return;
  }
  const map = L.map("farmMap", { scrollWheelZoom: false });
  L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 18, attribution: "&copy; OpenStreetMap contributors" }).addTo(map);
  const pts = [];
  located.forEach((f) => {
    const gps = f.locSource === "gps";
    const lat = f.lat + (gps ? 0 : jitter(f.id, 0)), lon = f.lon + (gps ? 0 : jitter(f.id, 1));
    pts.push([lat, lon]);
    L.circleMarker([lat, lon], {
      radius: 8, weight: 2, color: "#ffffff", fillColor: f.status === "active" ? "#1a9d5c" : "#8aa397", fillOpacity: 0.95,
    }).addTo(map).bindPopup(
      '<div class="ad-pin"><b>' + esc(f.name) + "</b><br>" + esc(locationText(f)) + "<br>" +
      esc(plural(f.openCrops, "crop")) + (gps ? " &middot; GPS pin" : " &middot; approximate") + "</div>");
  });
  map.fitBounds(pts, { padding: [40, 40], maxZoom: 12 });
}

function render(farmers, harvests, actsByUser) {
  const open = [];
  farmers.forEach((f) => f.crops.forEach((c) => { if (c.status !== "done") open.push({ ...c, farmer: f }); }));
  const active = farmers.filter((f) => f.status === "active").length;
  const area = open.reduce((s, c) => s + c.area, 0);
  const allH = [];
  Object.keys(harvests).forEach((uid) => toList(harvests[uid]).forEach((h) => allH.push(h)));
  const totalT = allH.reduce((s, h) => s + (Number(h.yield_t) || 0), 0);
  const sales = allH.reduce((s, h) => s + (Number(h.yield_t) || 0) * 1000 * (Number(h.price) || 0), 0);
  const attn = open.filter((c) => c.status === "warn" || c.status === "soon").sort((a, b) => (a.status === "warn" ? 0 : 1) - (b.status === "warn" ? 0 : 1));

  $("empty").style.display = farmers.length ? "none" : "block";
  $("sampleNote").style.display = farmers.some((f) => f.sample) ? "block" : "none";

  const tiles = [
    ["Farmers", farmers.length, active + " active, " + (farmers.length - active) + " inactive", ""],
    ["Crops growing", open.length, num(area) + " ha planted", ""],
    ["Total harvested", num(totalT) + " t", peso(sales) + " estimated sales", ""],
    ["Need attention", attn.length, attn.length ? "Dry or ready to harvest" : "Everything looks fine", attn.length ? "warn" : ""],
  ];
  $("stats").innerHTML = tiles.map((t) => '<div class="ad-stat ' + t[3] + '"><small>' + esc(t[0]) + "</small><b>" + esc(t[1]) + "</b><span>" + esc(t[2]) + "</span></div>").join("");

  // Harvested per month, last 6 months
  const now = todayISO();
  let y = Number(now.slice(0, 4)), m = Number(now.slice(5, 7));
  const months = [];
  for (let i = 0; i < 6; i++) { months.unshift({ key: y + "-" + String(m).padStart(2, "0"), label: MON[m - 1], v: 0 }); m--; if (m === 0) { m = 12; y--; } }
  allH.forEach((h) => { const mo = months.find((x) => x.key === (h.date || "").slice(0, 7)); if (mo) mo.v += Number(h.yield_t) || 0; });
  const max = Math.max(...months.map((x) => x.v), 0.1);
  $("chart").innerHTML = months.map((x) =>
    '<div class="ad-col ' + (x.v ? "" : "zero") + '"><b>' + num(x.v) + '</b><i style="height:' + Math.max(4, Math.round((x.v / max) * 130)) + 'px"></i><small>' + x.label + "</small></div>").join("");

  // Recently joined (with each farmer's own location)
  const recent = farmers.slice().sort((a, b) => (b.joined || "").localeCompare(a.joined || "")).slice(0, 5);
  $("joined").innerHTML = recent.length ? recent.map((f) =>
    '<div class="ad-row"><div><b>' + esc(f.name) + "</b><small>" + esc(locationText(f)) + (f.lat ? " &middot; " + (f.locSource === "gps" ? "GPS pin" : "town centre") : "") +
    '</small></div><span class="ad-pill ' + f.status + '">' + (f.status === "active" ? "Active" : "Inactive") + "</span></div>").join("") : none("No farmers have registered yet.");

  // By municipality
  const towns = {};
  farmers.forEach((f) => { const k = f.municipality || "Not set"; towns[k] = (towns[k] || 0) + 1; });
  $("towns").innerHTML = bars(Object.keys(towns).sort((a, b) => towns[b] - towns[a]).map((k) => ({ label: k, value: towns[k], text: plural(towns[k], "farmer") })));

  // Crop mix by area
  const mix = {};
  open.forEach((c) => { mix[c.name] = mix[c.name] || { a: 0, n: 0 }; mix[c.name].a += c.area; mix[c.name].n++; });
  $("mix").innerHTML = bars(Object.keys(mix).sort((a, b) => mix[b].a - mix[a].a).map((k) => ({ label: k, value: mix[k].a, text: num(mix[k].a) + " ha, " + plural(mix[k].n, "crop") })), "blue");

  // Needs attention
  $("attention").innerHTML = attn.length ? attn.slice(0, 6).map((c) =>
    '<div class="ad-row"><div><b>' + esc(c.name) + " &middot; " + esc(c.farmer.name) + "</b><small>" + esc(c.field) + ", " + esc(c.farmer.municipality ? c.farmer.municipality + (c.farmer.barangay ? " (Brgy. " + c.farmer.barangay + ")" : "") : "location not set") +
    '</small></div><span class="ad-pill ' + c.status + '">' + esc(c.label) + "</span></div>").join("") : none("No crops need attention.");

  // Recent activity
  const acts = [];
  farmers.forEach((f) => (actsByUser[f.id] || []).forEach((a) => acts.push({ date: a.date, ts: a.ts || 0, text: a.text, who: f.name })));
  acts.sort((a, b) => b.date.localeCompare(a.date) || b.ts - a.ts);
  $("activity").innerHTML = acts.length ? acts.slice(0, 7).map((a) =>
    '<div class="ad-row"><div><b>' + esc(a.text) + "</b><small>" + esc(a.who) + "</small></div><span>" + esc(ago(a.date)) + "</span></div>").join("") : none("No activity recorded yet.");

  drawMap(farmers);
}

requireUser({ role: "admin" }).then(async () => {
  try {
    const [users, crops, harvests, acts] = await Promise.all([listAllUsers(), listAll("crops"), listAll("harvests"), listAll("activities")]);
    const farmers = users.filter((u) => u.role !== "admin").map((u) => ({
      id: u.id, name: u.name || u.email || "(no name)", email: u.email || "", municipality: u.municipality || "", barangay: u.barangay || "",
      lat: Number(u.lat) || null, lon: Number(u.lon) || null, locSource: u.locationSource || "",
      joined: u.joined || "", status: u.status === "inactive" ? "inactive" : "active", sample: !!u.sample,
      crops: toList(crops[u.id]).filter((c) => c.planted && c.harvest).map(derive),
    }));
    farmers.forEach((f) => { f.openCrops = f.crops.filter((c) => c.status !== "done").length; });
    const actsByUser = {};
    Object.keys(acts).forEach((uid) => { actsByUser[uid] = toList(acts[uid]); });
    render(farmers, harvests, actsByUser);
  } catch (e) {
    console.error(e);
    showError(explain(e, "Could not load the dashboard."));
  }
});