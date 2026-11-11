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

// Shared styles (buttons, dialogs, form fields) are injected here, so no extra CSS file has to be copied.
const AG_CSS = `/* assets/css/agri-ui.css
   Shared pieces used by pages that read/write Firebase: buttons, dialog, form fields, empty state, toast.
   Every class is prefixed ag- so nothing clashes with dashboard.css or the page styles. */
.ag-btn { font: inherit; font-size: .86rem; font-weight: 700; cursor: pointer; padding: 9px 16px; border-radius: 999px; border: 1px solid #1a9d5c; background: #1a9d5c; color: #fff; transition: background .15s, box-shadow .15s; }
.ag-btn:hover { background: #148a4f; }
.ag-btn:focus-visible, .ag-ghost:focus-visible, .ag-x:focus-visible { outline: 3px solid rgba(26,157,92,.35); outline-offset: 2px; }
.ag-btn[disabled] { opacity: .6; cursor: default; }
.ag-ghost { font: inherit; font-size: .84rem; font-weight: 700; cursor: pointer; padding: 7px 14px; border-radius: 999px; border: 1px solid #e1eee6; background: #fff; color: #35584a; transition: border-color .15s, color .15s; }
.ag-ghost:hover { border-color: #1a9d5c; color: #0c4a30; }
.ag-ghost.danger { color: #b13a2f; }
.ag-ghost.danger:hover { border-color: #d6453d; color: #b13a2f; }
.ag-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }

.ag-empty { padding: 44px 24px; text-align: center; color: #6b8a7a; background: #fff; border: 1px dashed #cfe3d6; border-radius: 20px; }
.ag-empty b { display: block; color: #0c4a30; font-size: 1.05rem; margin-bottom: 6px; }
.ag-empty p { margin: 0 auto 16px; max-width: 420px; line-height: 1.5; }
.ag-empty .ag-actions { justify-content: center; }
.ag-loading { padding: 32px; text-align: center; color: #6b8a7a; }

dialog.ag-dlg { border: 0; border-radius: 20px; padding: 0; width: min(480px, calc(100vw - 32px)); color: #12382a; font-family: inherit; box-shadow: 0 24px 60px rgba(12,74,48,.28); }
dialog.ag-dlg::backdrop { background: rgba(12,40,28,.45); }
.ag-dlg form { padding: 24px; }
.ag-dlg h2 { margin: 0 0 4px; font-size: 1.2rem; color: #0c4a30; }
.ag-dlg .ag-sub { margin: 0 0 18px; color: #6b8a7a; font-size: .88rem; }
.ag-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.ag-field { display: block; margin-bottom: 12px; }
.ag-field span { display: block; margin-bottom: 5px; font-size: .74rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #6b8a7a; }
.ag-field input, .ag-field select { width: 100%; font: inherit; font-size: .95rem; padding: 10px 12px; border-radius: 12px; border: 1px solid #d3e4d9; background: #fff; color: #12382a; }
.ag-field input:focus, .ag-field select:focus { outline: 3px solid rgba(26,157,92,.25); border-color: #1a9d5c; }
.ag-err { min-height: 1.2em; margin: 0 0 8px; color: #b13a2f; font-size: .86rem; font-weight: 600; }
.ag-foot { display: flex; justify-content: flex-end; gap: 8px; margin-top: 6px; }

.ag-toast { position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%); z-index: 99; background: #0c4a30; color: #fff; padding: 11px 20px; border-radius: 999px; font-size: .9rem; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,.2); }
@media (max-width: 480px) { .ag-grid2 { grid-template-columns: 1fr; } }
`;
if (typeof document !== "undefined" && !document.getElementById("ag-ui-css")) {
  const st = document.createElement("style");
  st.id = "ag-ui-css"; st.textContent = AG_CSS;
  document.head.appendChild(st);
}

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
// FIX: ref(database, "") throws "path argument was an invalid path". The database root
// must be requested with ref(database) and no path, so an empty path maps to the root.
const R = (path) => (path ? ref(database, path) : ref(database));
async function val(path) { const s = await get(R(path)); return s.exists() ? s.val() : null; }
export const toList = (obj) => (obj ? Object.entries(obj).map(([id, v]) => ({ ...v, id })) : []);

/* ------------------------------------------------------------------ profile + session */
// Profiles written by the register page use fullName, role "user" and createdAt. This gives every page one shape.
export function normUser(u) {
  if (!u) return u;
  let joined = u.joined;
  if (!joined && Number(u.createdAt)) joined = new Intl.DateTimeFormat("en-CA", { timeZone: "Asia/Manila" }).format(new Date(Number(u.createdAt)));
  return { ...u, name: u.name || u.fullName || (u.email || "").split("@")[0] || "Farmer", status: u.status === "inactive" ? "inactive" : "active", joined: joined || todayISO() };
}
export async function getProfile(uid) { return normUser(await val("users/" + uid)); }

export async function ensureProfile(user, extra) {
  let p = await getProfile(user.uid);
  if (!p) {
    p = Object.assign({
      name: user.displayName || (user.email || "").split("@")[0] || "Farmer",
      email: user.email || "", phone: "", municipality: "", barangay: "", farm_size: 0,
      role: "user", status: "active", joined: todayISO(), lastActive: todayISO(),
    }, extra || {});
    await set(R("users/" + user.uid), p);
  }
  return normUser(p);
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
        const profile = await Promise.race([
          ensureProfile(user),
          new Promise((_, rej) => setTimeout(() => rej(new Error("Firebase did not answer within 15 seconds. Check the databaseURL in firebase.js and your connection.")), 15000)),
        ]);
        if (profile.status === "inactive") { await signOut(auth); location.href = "../index.html?deactivated=1"; return; }
        if (role && profile.role !== role) { location.href = homeFor(profile); return; }
        update(R("users/" + user.uid), { lastActive: todayISO() }).catch(() => {});
        const w = document.getElementById("welcome");
        if (w) w.textContent = "Welcome, " + (profile.name || user.displayName || user.email) + "!";
        if (profile.role !== "admin") {
          // The dashboard weather box reads the saved location as the page loads, so reload once if it just changed
          if (syncFarmLocation(profile) && document.getElementById("dashTemp")) { location.reload(); return; }
          showLocationBar(user.uid, profile);
          if (!hasLocation(profile)) setTimeout(() => openLocationDialog(user.uid, profile, (p) => showLocationBar(user.uid, p)), 400);
        }
        resolve({ user, profile, uid: user.uid });
      } catch (err) {
        console.error(err);
        showError(explain(err, "Could not load your data from Firebase."));
      }
    });
  });
}

// Turns a Firebase error into a sentence that says what is actually wrong.
export function explain(e, what) {
  const raw = ((e && (e.code || "")) + " " + ((e && e.message) || "")).trim();
  if (/permission/i.test(raw)) return what + " Firebase denied access: publish database.rules.json under Realtime Database > Rules, then reload. (" + raw + ")";
  return what + " " + (raw || "Unknown error") + " (see the browser console for details)";
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


/* ------------------------------------------------------------------ farm location */
// Town centres (approximate). A farmer can refine this with their device location.
export const TOWN_COORDS = {"Alaminos": [14.0639, 121.2461], "Bay": [14.1833, 121.2833], "Biñan": [14.3333, 121.0833], "Cabuyao": [14.2724, 121.1251], "Calamba": [14.2117, 121.1653], "Calauan": [14.145, 121.3171], "Cavinti": [14.2433, 121.5096], "Famy": [14.4386, 121.4492], "Kalayaan": [14.3253, 121.4608], "Liliw": [14.1286, 121.4339], "Los Baños": [14.1699, 121.2415], "Luisiana": [14.1839, 121.5103], "Lumban": [14.2989, 121.4631], "Mabitac": [14.4297, 121.4261], "Magdalena": [14.2, 121.4333], "Majayjay": [14.1469, 121.4736], "Nagcarlan": [14.1364, 121.4153], "Paete": [14.3647, 121.4836], "Pagsanjan": [14.2736, 121.4547], "Pakil": [14.3786, 121.4781], "Pangil": [14.4042, 121.465], "Pila": [14.2333, 121.3667], "Rizal": [14.1111, 121.3939], "San Pablo City": [14.0689, 121.3256], "San Pedro": [14.3595, 121.0473], "Santa Cruz": [14.2781, 121.4161], "Santa Maria": [14.4678, 121.4283], "Santa Rosa": [14.3122, 121.1114], "Siniloan": [14.4169, 121.4486], "Victoria": [14.2242, 121.3306]};

// The farm location is set once and then stays fixed. Every page reads it from here.
export function farmLocation(p) {
  if (!hasLocation(p)) return null;
  const c = TOWN_COORDS[p.municipality] || [14.1833, 121.2833];
  return { name: p.municipality + ", Laguna, Philippines", lat: Number(p.lat) || c[0], lon: Number(p.lon) || c[1] };
}
// Returns true when the saved weather location had to be changed to match the farm.
export function syncFarmLocation(p) {
  const loc = farmLocation(p);
  if (!loc) return false;
  const next = JSON.stringify(loc);
  try {
    if (localStorage.getItem("agritrack.location") === next) return false;
    localStorage.setItem("agritrack.location", next);
    return true;
  } catch (e) { return false; }
}

export const hasLocation = (p) => !!(p && p.municipality && p.barangay);
export const locationText = (p) => (p && p.municipality ? (p.barangay ? "Brgy. " + p.barangay + ", " : "") + p.municipality + ", Laguna" : "Not set");

// Dialog where a farmer sets where their farm is. Saved to users/{uid}; the weather page uses it too.
export function openLocationDialog(uid, profile, onSaved) {
  let dlg = document.getElementById("locDlg");
  if (dlg) dlg.remove();
  dlg = document.createElement("dialog");
  dlg.id = "locDlg"; dlg.className = "ag-dlg";
  const opts = Object.keys(TOWN_COORDS).map((t) => '<option value="' + esc(t) + '"' + (profile.municipality === t ? " selected" : "") + ">" + esc(t) + "</option>").join("");
  dlg.innerHTML = '<form method="dialog" novalidate><h2>Where is your farm?</h2>' +
    '<p class="ag-sub">Set once. Admins see it on your profile, and your weather, markets and recommendations all use it.</p>' +
    '<label class="ag-field"><span>Municipality / city (Laguna)</span><select name="town"><option value="">Choose&hellip;</option>' + opts + "</select></label>" +
    '<label class="ag-field"><span>Barangay</span><input name="brgy" autocomplete="off" value="' + esc(profile.barangay || "") + '" placeholder="e.g. San Antonio"></label>' +
    '<div class="ag-actions" style="margin:0 0 10px"><button type="button" class="ag-ghost" id="locGps">Use my current location</button></div>' +
    '<p class="ag-sub" id="locInfo" style="margin:0 0 8px">' + (profile.lat ? "Pin saved: " + Number(profile.lat).toFixed(4) + ", " + Number(profile.lon).toFixed(4) : "Optional: adds an exact pin to your profile.") + "</p>" +
    '<p class="ag-err" id="locErr" role="alert"></p>' +
    '<div class="ag-foot"><button type="button" class="ag-ghost" data-close>Later</button><button type="submit" class="ag-btn">Save location</button></div></form>';
  document.body.appendChild(dlg);
  const f = dlg.querySelector("form");
  let pin = profile.lat ? { lat: Number(profile.lat), lon: Number(profile.lon), src: profile.locationSource || "town" } : null;
  dlg.querySelector("[data-close]").addEventListener("click", () => dlg.close());
  f.town.addEventListener("change", () => { if (!pin || pin.src === "town") pin = null; });
  dlg.querySelector("#locGps").addEventListener("click", () => {
    const info = dlg.querySelector("#locInfo");
    if (!navigator.geolocation) { info.textContent = "This browser cannot share its location."; return; }
    info.textContent = "Asking your device for its location\u2026";
    navigator.geolocation.getCurrentPosition(
      (pos) => { pin = { lat: Math.round(pos.coords.latitude * 1e5) / 1e5, lon: Math.round(pos.coords.longitude * 1e5) / 1e5, src: "gps" }; info.textContent = "Pin set: " + pin.lat.toFixed(4) + ", " + pin.lon.toFixed(4) + ". Pick your municipality and barangay too."; },
      () => { info.textContent = "Location was blocked. Choose your town and barangay instead."; },
      { enableHighAccuracy: true, timeout: 10000 });
  });
  f.addEventListener("submit", async (e) => {
    e.preventDefault();
    const town = f.town.value, brgy = f.brgy.value.trim(), err = dlg.querySelector("#locErr");
    if (!town) return (err.textContent = "Choose your municipality or city.");
    if (!brgy) return (err.textContent = "Enter your barangay.");
    const c = TOWN_COORDS[town];
    const at = pin || { lat: c[0], lon: c[1], src: "town" };
    const patch = { municipality: town, barangay: brgy, lat: at.lat, lon: at.lon, locationSource: at.src };
    try {
      await update(R("users/" + uid), patch);
      try { localStorage.setItem("agritrack.location", JSON.stringify({ name: town + ", Laguna, Philippines", lat: at.lat, lon: at.lon })); } catch (x) {}
      dlg.close(); toast("Farm location saved");
      if (document.getElementById("dashTemp")) setTimeout(() => location.reload(), 600);
      if (onSaved) onSaved(Object.assign({}, profile, patch));
    } catch (ex) { err.textContent = explain(ex, "Could not save."); }
  });
  dlg.showModal();
}

// A slim bar at the top of each farmer page showing the saved location, with a Change button.
function showLocationBar(uid, profile) {
  let bar = document.getElementById("locBar");
  if (!bar) {
    bar = document.createElement("div");
    bar.id = "locBar";
    bar.style.cssText = "display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 14px;font:600 .86rem 'Plus Jakarta Sans',sans-serif;color:#35584a";
    const main = document.querySelector("main") || document.body;
    main.insertBefore(bar, main.firstChild);
  }
  bar.innerHTML = '<span aria-hidden="true">\u{1F4CD}</span><span>Farm location: <b>' + esc(locationText(profile)) + '</b></span>' + (hasLocation(profile)
    ? '<span style="font-weight:500;color:#6b8a7a">Fixed for your account. Ask an administrator if it needs to change.</span>'
    : '<button type="button" class="ag-ghost" style="padding:4px 12px">Set location</button>');
  const btn = bar.querySelector("button");
  if (btn) btn.addEventListener("click", () => openLocationDialog(uid, profile, (p) => showLocationBar(uid, p)));
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
export async function listAllUsers() { return toList(await val("users")).map(normUser); }
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
      farm_size: f.farm_size, role: "user", status: f.status, joined: at(f.joined), lastActive: at(f.lastActive), sample: true,
    };
    const tc = TOWN_COORDS[f.municipality];
    if (tc) Object.assign(multi["users/" + uid], { lat: tc[0], lon: tc[1], locationSource: "town" });
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