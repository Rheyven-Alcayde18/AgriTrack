// assets/js/db.js
// The one place AgriTrack talks to Firebase Realtime Database.
// Every page imports from here, so the data shape lives in a single file.
//
// Database layout (see database.rules.json for who may read/write what):
//   users/{uid}          profile: name, email, phone, municipality, barangay, farm_size, role, status, joined, lastActive
//   crops/{uid}/{id}     name, field, area, planted, harvest, moisture, yield_t, price, harvestedOn?
//   harvests/{uid}/{id}  date, crop, field, yield_t, price
//   savedMarkets/{uid}   { marketId: true }
//   activities/{uid}/{id} date, ts, text
//   checklist/{uid}      { key: true }   (harvest prep ticks + completed recommendations)
//   markets/{id}         shared market listings (admin edits, everyone reads)
import { auth, database } from "./firebase.js";
import { onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/11.0.2/firebase-auth.js";
import { ref, get, set, update, remove, push } from "https://www.gstatic.com/firebasejs/11.0.2/firebase-database.js";
import { DEFAULT_MARKETS, SAMPLE_FARMERS, SAMPLE_MY_CROPS, SAMPLE_MY_HARVESTS } from "./sample-data.js";

export { auth, database };

/* ------------------------------------------------------------------ small helpers */
export const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
export const num = (n) => String(Math.round(Number(n) * 10) / 10);
export const peso = (n) => "₱" + Math.round(Number(n) || 0).toLocaleString("en-US");
const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
export const fmtMD = (iso) => { const [, m, d] = iso.split("-").map(Number); return MONTHS[m - 1] + " " + d; };
export const fmtMon = (iso) => MONTHS[Number(iso.split("-")[1]) - 1];
export const fmtDay = (iso) => String(Number(iso.split("-")[2]));
export const fmtFull = (iso) => fmtMD(iso) + ", " + iso.split("-")[0];

// Today's date in the Philippines, as YYYY-MM-DD
export function todayISO() {
  return new Intl.DateTimeFormat("en-CA", { timeZone: "Asia/Manila", year: "numeric", month: "2-digit", day: "2-digit" }).format(new Date());
}
const toUTC = (iso) => { const [y, m, d] = iso.split("-").map(Number); return Date.UTC(y, m - 1, d); };
export function addDays(iso, n) { return new Date(toUTC(iso) + n * 86400000).toISOString().slice(0, 10); }
export function dayDiff(a, b) { return Math.round((toUTC(b) - toUTC(a)) / 86400000); }
export const daysText = (d) => (d > 0 ? "In " + d + " day" + (d === 1 ? "" : "s") : d === 0 ? "Today" : "Ready now");

/* ------------------------------------------------------------------ crop rules */
export const STAGES = ["Seedling", "Vegetative", "Flowering", "Fruiting", "Harvest ready"];

// Used by the "Add crop" form to fill in sensible numbers. The farmer can change all of them.
export const CROP_DEFAULTS = {
  Rice:     { days: 120, yield_per_ha: 0.8,  price: 20, prep: ["Reserve harvester", "Arrange drying space", "Confirm buyer"] },
  Corn:     { days: 110, yield_per_ha: 0.69, price: 14, prep: ["Reserve sheller", "Prepare sacks", "Confirm buyer"] },
  Tomato:   { days: 75,  yield_per_ha: 0.6,  price: 40, prep: ["Book transport", "Hire pickers", "Prepare crates"] },
  Eggplant: { days: 100, yield_per_ha: 0.4,  price: 45, prep: ["Plan picking crew", "Prepare crates", "Confirm buyer"] },
};
const GENERIC_PREP = ["Arrange transport", "Prepare containers", "Confirm buyer"];

// Progress, stage and status are always CALCULATED from the dates, so no page can disagree with another.
export function derive(c) {
  const today = todayISO();
  const total = Math.max(1, dayDiff(c.planted, c.harvest));
  const elapsed = dayDiff(c.planted, today);
  const pct = (elapsed / total) * 100;
  const days = dayDiff(today, c.harvest);
  const moisture = Number(c.moisture) || 0;
  const out = { ...c, area: Number(c.area) || 0, moisture, yield_t: Number(c.yield_t) || 0, price: Number(c.price) || 0, days };
  out.progress = Math.max(0, Math.min(100, Math.round(pct)));
  out.stage = pct < 15 ? "Seedling" : pct < 45 ? "Vegetative" : pct < 70 ? "Flowering" : pct < 90 ? "Fruiting" : "Harvest ready";
  out.stageIndex = STAGES.indexOf(out.stage);
  out.moistureLow = moisture < 35;
  out.revenue = out.yield_t * 1000 * out.price;
  out.prep = (CROP_DEFAULTS[c.name] || {}).prep || GENERIC_PREP;
  if (c.harvestedOn) {
    Object.assign(out, { status: "done", label: "Harvested", progress: 100, stage: "Harvest ready", stageIndex: 4, action: "Harvested on " + fmtFull(c.harvestedOn) + "." });
  } else if (moisture < 35) {
    Object.assign(out, { status: "warn", label: "Needs water", action: "Water today. Soil moisture is below the healthy range." });
  } else if (days <= 14) {
    Object.assign(out, { status: "soon", label: days <= 0 ? "Ready now" : "Harvest soon", action: "Book transport and prepare crates for picking." });
  } else {
    Object.assign(out, { status: "ok", label: "On track", action: "Keep checking soil moisture and watch the weather forecast." });
  }
  // Harvest-page wording
  if (out.status === "done") { out.hs = "done"; out.hl = "Harvested"; }
  else if (days <= 0) { out.hs = "soon"; out.hl = "Ready now"; }
  else if (days <= 14) { out.hs = "soon"; out.hl = "Harvest soon"; }
  else if (days <= 45) { out.hs = "upcoming"; out.hl = "Upcoming"; }
  else { out.hs = "growing"; out.hl = "Growing"; }
  return out;
}

/* ------------------------------------------------------------------ raw reads/writes */
const R = (path) => ref(database, path);
async function val(path) { const s = await get(R(path)); return s.exists() ? s.val() : null; }
export const toList = (obj) => (obj ? Object.entries(obj).map(([id, v]) => ({ ...v, id })) : []);

/* ------------------------------------------------------------------ profile + session */
export async function getProfile(uid) { return val("users/" + uid); }

export async function ensureProfile(user, extra) {
  let p = await getProfile(user.uid);
  if (!p) {
    p = Object.assign({
      name: user.displayName || (user.email || "").split("@")[0] || "Farmer",
      email: user.email || "", phone: "", municipality: "Bay", barangay: "", farm_size: 0,
      role: "farmer", status: "active", joined: todayISO(), lastActive: todayISO(),
    }, extra || {});
    await set(R("users/" + user.uid), p);
  }
  return p;
}

export function homeFor(profile) { return profile && profile.role === "admin" ? "../admin/dashboard.php" : "../user/dashboard.php"; }

// Resolves with { user, profile, uid } once a signed-in, active user of the right role is present.
// Otherwise it redirects (to sign-in, or to the farmer dashboard) and never resolves.
export function requireUser(opts) {
  const role = opts && opts.role;
  return new Promise((resolve) => {
    onAuthStateChanged(auth, async (user) => {
      if (!user) { location.href = "../index.html"; return; }
      try {
        const profile = await ensureProfile(user);
        if (profile.status === "inactive") { await signOut(auth); location.href = "../index.html?deactivated=1"; return; }
        if (role && profile.role !== role) { location.href = homeFor(profile); return; }
        update(R("users/" + user.uid), { lastActive: todayISO() }).catch(() => {});
        const w = document.getElementById("welcome");
        if (w) w.textContent = "Welcome, " + (profile.name || user.displayName || user.email) + "!";
        resolve({ user, profile, uid: user.uid });
      } catch (err) {
        console.error(err);
        showError("Could not load your data from Firebase. " + (err && err.code === "PERMISSION_DENIED" ? "Check that the database rules were published." : "Check your connection and reload."));
      }
    });
  });
}

export function showError(text) {
  let box = document.getElementById("fbError");
  if (!box) {
    box = document.createElement("div");
    box.id = "fbError"; box.setAttribute("role", "alert");
    box.style.cssText = "margin:0 0 16px;padding:12px 16px;border-radius:12px;background:#fde6e3;color:#8a2a21;font:600 .9rem 'Plus Jakarta Sans',sans-serif";
    const main = document.querySelector("main") || document.body;
    main.insertBefore(box, main.firstChild);
  }
  box.textContent = text;
}

/* ------------------------------------------------------------------ farmer data */
export async function listCrops(uid) { return toList(await val("crops/" + uid)).map(derive); }

export async function saveCrop(uid, crop) {
  const id = crop.id || push(R("crops/" + uid)).key;
  const { id: _drop, ...rest } = crop;
  const clean = {};
  Object.keys(rest).forEach((k) => { if (rest[k] !== undefined && rest[k] !== null && rest[k] !== "") clean[k] = rest[k]; });
  await set(R("crops/" + uid + "/" + id), clean);
  await logActivity(uid, (crop.id ? "Updated " : "Registered ") + crop.name.toLowerCase() + " crop in " + crop.field);
  return id;
}

export async function deleteCrop(uid, crop) {
  await remove(R("crops/" + uid + "/" + crop.id));
  await logActivity(uid, "Removed " + crop.name.toLowerCase() + " crop from " + crop.field);
}

export async function logMoisture(uid, crop, moisture) {
  await update(R("crops/" + uid + "/" + crop.id), { moisture: Number(moisture) });
  await logActivity(uid, "Logged soil moisture reading for " + crop.field + " (" + moisture + "%)");
}

// Marks a crop harvested and writes a row to the harvest history in one atomic update
export async function recordHarvest(uid, crop, h) {
  const hid = push(R("harvests/" + uid)).key;
  const date = h.date || todayISO();
  await update(R(""), {
    ["harvests/" + uid + "/" + hid]: { date, crop: crop.name, field: crop.field, yield_t: Number(h.yield_t), price: Number(h.price), cropId: crop.id },
    ["crops/" + uid + "/" + crop.id + "/harvestedOn"]: date,
  });
  await logActivity(uid, "Marked " + crop.name.toLowerCase() + " as harvested");
}

export async function listHarvests(uid) {
  return toList(await val("harvests/" + uid)).sort((a, b) => (a.date < b.date ? 1 : -1));
}

export async function getSaved(uid) { return Object.keys((await val("savedMarkets/" + uid)) || {}); }
export async function setSaved(uid, marketId, on) {
  await (on ? set(R("savedMarkets/" + uid + "/" + marketId), true) : remove(R("savedMarkets/" + uid + "/" + marketId)));
  const m = (await val("markets/" + marketId)) || DEFAULT_MARKETS[marketId];
  if (on && m) logActivity(uid, "Saved " + m.name).catch(() => {});
}

export async function logActivity(uid, text) {
  await set(push(R("activities/" + uid)), { date: todayISO(), ts: Date.now(), text });
}
export async function listActivities(uid, limit) {
  const all = toList(await val("activities/" + uid)).sort((a, b) => (b.ts || 0) - (a.ts || 0));
  return limit ? all.slice(0, limit) : all;
}

export async function getChecklist(uid) { return (await val("checklist/" + uid)) || {}; }
export async function setCheck(uid, key, on) {
  await (on ? set(R("checklist/" + uid + "/" + key), true) : remove(R("checklist/" + uid + "/" + key)));
}

/* ------------------------------------------------------------------ markets */
export async function loadMarkets() {
  const data = (await val("markets")) || DEFAULT_MARKETS;
  return toList(data).sort((a, b) => a.name.localeCompare(b.name));
}
export async function seedMarkets() {
  const existing = await val("markets");
  if (!existing) await set(R("markets"), DEFAULT_MARKETS);
  return !existing;
}

/* ------------------------------------------------------------------ admin helpers */
export async function listAllUsers() { return toList(await val("users")); }
export async function listAll(node) { return (await val(node)) || {}; } // object keyed by uid
export async function updateUser(uid, patch) { await update(R("users/" + uid), patch); }

const NODES = ["users", "crops", "harvests", "savedMarkets", "activities", "checklist"];

// Writes the 12 demo farmers (with crops, harvests, saved markets, activities) in one update.
export async function seedSample() {
  const today = todayISO();
  const at = (n) => addDays(today, n);
  const multi = {};
  SAMPLE_FARMERS.forEach((f, i) => {
    const uid = "sample-f" + String(i + 1).padStart(3, "0");
    multi["users/" + uid] = {
      name: f.name, email: f.email, phone: f.phone, municipality: f.municipality, barangay: f.barangay,
      farm_size: f.farm_size, role: "farmer", status: f.status, joined: at(f.joined), lastActive: at(f.lastActive), sample: true,
    };
    f.crops.forEach((c, j) => {
      const o = { name: c.name, field: c.field, area: c.area, planted: at(c.planted), harvest: at(c.harvest), moisture: c.moisture };
      const d = CROP_DEFAULTS[c.name] || {};
      o.yield_t = Math.round(c.area * (d.yield_per_ha || 0.5) * 10) / 10;
      o.price = d.price || 20;
      if (c.harvestedOn !== undefined) o.harvestedOn = at(c.harvestedOn);
      multi["crops/" + uid + "/c" + (j + 1)] = o;
    });
    f.harvests.forEach((h, j) => { multi["harvests/" + uid + "/h" + (j + 1)] = { date: at(h.d), crop: h.crop, yield_t: h.yield_t, price: h.price }; });
    f.activities.forEach((a, j) => { multi["activities/" + uid + "/a" + String(j + 1).padStart(2, "0")] = { date: at(a.d), ts: toUTC(at(a.d)) + 43200000 - j, text: a.text }; });
    f.markets.forEach((m) => { multi["savedMarkets/" + uid + "/" + m] = true; });
  });
  await update(R(""), multi);
  await seedMarkets();
  return SAMPLE_FARMERS.length;
}

// Gives the signed-in account the demo farm (4 crops + 3 past harvests) so empty pages have something to show.
export async function seedMyFarm(uid) {
  const today = todayISO();
  const multi = {};
  SAMPLE_MY_CROPS.forEach((c, i) => {
    multi["crops/" + uid + "/m" + (i + 1)] = { name: c.name, field: c.field, area: c.area, planted: addDays(today, c.planted), harvest: addDays(today, c.harvest), moisture: c.moisture, yield_t: c.yield_t, price: c.price };
  });
  SAMPLE_MY_HARVESTS.forEach((h, i) => {
    multi["harvests/" + uid + "/m" + (i + 1)] = { date: addDays(today, h.d), crop: h.crop, field: h.field, yield_t: h.yield_t, price: h.price };
  });
  await update(R(""), multi);
}

export async function clearSample() {
  const users = await val("users");
  const ids = Object.keys(users || {}).filter((id) => users[id] && users[id].sample);
  const multi = {};
  ids.forEach((id) => NODES.forEach((n) => { multi[n + "/" + id] = null; }));
  if (ids.length) await update(R(""), multi);
  return ids.length;
}

export async function signOutAndGo() { await signOut(auth); location.href = "../index.html"; }

/* ------------------------------------------------------------------ for the classic scripts */
// markets.js and recommendations.js are plain scripts that read window.AGRITRACK_DATA.
// This sets that object, then loads the script.
export function bootClassic(data, src, globalName) {
  window[globalName || "AGRITRACK_DATA"] = data;
  const s = document.createElement("script");
  s.src = src;
  document.body.appendChild(s);
}

/* ------------------------------------------------------------------ tiny UI helper */
export function toast(text) {
  const t = document.createElement("div");
  t.className = "ag-toast"; t.setAttribute("role", "status"); t.textContent = text;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2600);
}