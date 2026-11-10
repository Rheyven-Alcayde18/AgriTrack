// assets/js/admin-data.js
// Shared by every admin page: loads farmers, crops and harvests from Firebase (through db.js),
// joins them together, and provides small UI helpers (toast, confirm dialog, pager).
import { listAllUsers, listAll, derive, dayDiff, todayISO, fmtFull } from "./db.js";

export const r2 = (n) => Math.round((Number(n) || 0) * 100) / 100;

/* ------------------------------------------------------------------ data */
// Returns { farmers, crops, harvests }.
//   farmers[i].crops / .harvests   their own records
//   farmers[i].cropArea            total crop area in ha
//   farmers[i].harvestYield        total harvested tonnes
//   farmers[i].harvestRevenue      total harvest revenue in PHP
//   crops[i]                       derived crop (stage, status, label, days, progress...) plus uid, farmer, municipality
//   harvests[i]                    harvest record plus uid, farmer, municipality, revenue
export async function loadAdminData() {
  const [users, cropsBy, harvestsBy] = await Promise.all([listAllUsers(), listAll("crops"), listAll("harvests")]);

  const farmers = users
    .filter((u) => u.role !== "admin")
    .map((u) => ({ ...u, crops: [], harvests: [] }))
    .sort((a, b) => String(a.name).localeCompare(String(b.name)));

  const byId = {};
  farmers.forEach((f) => { byId[f.id] = f; });

  const crops = [];
  const harvests = [];

  Object.keys(cropsBy || {}).forEach((uid) => {
    const f = byId[uid];
    if (!f) return; // crops of an account that no longer exists
    Object.entries(cropsBy[uid] || {}).forEach(([id, raw]) => {
      let c;
      try {
        c = derive({ ...raw, id });
      } catch (e) {
        // a malformed record (for example a missing date) must not break the whole page
        c = {
          ...raw, id,
          area: Number(raw.area) || 0, yield_t: Number(raw.yield_t) || 0, price: Number(raw.price) || 0,
          moisture: Number(raw.moisture) || 0, days: 0, progress: 0, stage: "-",
          status: "ok", label: "On track", revenue: 0, action: ""
        };
      }
      c.uid = uid;
      c.farmer = f.name;
      c.municipality = f.municipality || "";
      crops.push(c);
      f.crops.push(c);
    });
  });

  Object.keys(harvestsBy || {}).forEach((uid) => {
    const f = byId[uid];
    if (!f) return;
    Object.entries(harvestsBy[uid] || {}).forEach(([id, raw]) => {
      const y = Number(raw.yield_t) || 0;
      const p = Number(raw.price) || 0;
      const h = { ...raw, id, uid, farmer: f.name, municipality: f.municipality || "", yield_t: y, price: p, revenue: y * 1000 * p };
      harvests.push(h);
      f.harvests.push(h);
    });
  });

  farmers.forEach((f) => {
    f.cropArea = r2(f.crops.reduce((n, c) => n + (Number(c.area) || 0), 0));
    f.harvestYield = r2(f.harvests.reduce((n, h) => n + h.yield_t, 0));
    f.harvestRevenue = Math.round(f.harvests.reduce((n, h) => n + h.revenue, 0));
  });

  harvests.sort((a, b) => (a.date < b.date ? 1 : -1));

  return { farmers, crops, harvests };
}

/* ------------------------------------------------------------------ text helpers */
export function initials(name) {
  const parts = String(name || "?").trim().split(/\s+/).slice(0, 2);
  return parts.map((p) => p.charAt(0).toUpperCase()).join("") || "?";
}

export function locationOf(f) {
  if (!f || !f.municipality) return "Not set";
  return (f.barangay ? "Brgy. " + f.barangay + ", " : "") + f.municipality;
}

export function agoText(iso) {
  if (!iso) return "Never";
  const d = dayDiff(iso, todayISO());
  if (d <= 0) return "Today";
  if (d === 1) return "Yesterday";
  if (d < 30) return d + " days ago";
  return fmtFull(iso);
}

/* ------------------------------------------------------------------ UI helpers */
export function toast(text) {
  const t = document.createElement("div");
  t.className = "toast";
  t.setAttribute("role", "status");
  t.textContent = text;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2800);
}

// Uses the #confirmDlg that admin/includes/footer.php adds to every page.
// Resolves true when confirmed, false when cancelled or dismissed.
export function confirmAction(title, text, okLabel, danger) {
  const dlg = document.getElementById("confirmDlg");
  const ok = document.getElementById("confirmOk");
  const cancel = document.getElementById("confirmCancel");

  document.getElementById("confirmTitle").textContent = title;
  document.getElementById("confirmText").textContent = text;
  ok.textContent = okLabel;
  ok.className = danger ? "btn solid-danger" : "btn primary";

  return new Promise((resolve) => {
    function done(result) {
      ok.removeEventListener("click", onOk);
      cancel.removeEventListener("click", onCancel);
      dlg.removeEventListener("close", onCancel);
      dlg.removeEventListener("click", onBackdrop);
      if (dlg.open) dlg.close();
      resolve(result);
    }
    function onOk() { done(true); }
    function onCancel() { done(false); }
    function onBackdrop(e) { if (e.target === dlg) done(false); }

    ok.addEventListener("click", onOk);
    cancel.addEventListener("click", onCancel);
    dlg.addEventListener("close", onCancel);
    dlg.addEventListener("click", onBackdrop);
    dlg.showModal();
  });
}

// Close any <dialog> when its backdrop is clicked
export function closeOnBackdrop(dlg) {
  dlg.addEventListener("click", (e) => { if (e.target === dlg) dlg.close(); });
}

// Previous / Next buttons under a table
export function renderPager(el, total, page, size, onPage) {
  const pages = Math.max(1, Math.ceil(total / size));
  if (total <= size) { el.innerHTML = ""; el.onclick = null; return; }
  el.innerHTML =
    "<span>Page " + page + " of " + pages + "</span><div>" +
    '<button type="button" class="btn small" data-p="' + (page - 1) + '"' + (page <= 1 ? " disabled" : "") + ">Previous</button>" +
    '<button type="button" class="btn small" data-p="' + (page + 1) + '"' + (page >= pages ? " disabled" : "") + ">Next</button></div>";
  el.onclick = (e) => {
    const b = e.target.closest("[data-p]");
    if (b && !b.disabled) onPage(Number(b.dataset.p));
  };
}

// Keep a page number valid after the list got shorter
export function clampPage(page, total, size) {
  return Math.min(Math.max(1, page), Math.max(1, Math.ceil(total / size)));
}