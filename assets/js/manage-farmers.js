// assets/js/manage-farmers.js
// Admin > Manage farmers, connected to Firebase Realtime Database.
// Reads users, crops, harvests, activities and savedMarkets (admin-only by the database rules),
// then starts farmers.js. Edits and activate/deactivate are written to users/{uid}.
// Loaded by admin/manage-farmers.php.
import {
  requireUser, listAllUsers, listAll, loadMarkets, updateUser, seedSample, clearSample,
  derive, toList, bootClassic, toast, showError
} from "./db.js";

const $ = (id) => document.getElementById(id);

requireUser({ role: "admin" }).then(async () => {
  try {
    const [users, crops, harvests, acts, saved, markets] = await Promise.all([
      listAllUsers(), listAll("crops"), listAll("harvests"), listAll("activities"), listAll("savedMarkets"), loadMarkets(),
    ]);
    const farmers = users.filter((u) => u.role !== "admin").map((u) => ({
      id: u.id, name: u.name || "(no name)", email: u.email || "", phone: u.phone || "",
      municipality: u.municipality || "", barangay: u.barangay || "", farm_size: Number(u.farm_size) || 0,
      joined: u.joined || "2026-01-01", last_active: u.lastActive || u.joined || "2026-01-01",
      status: u.status === "inactive" ? "inactive" : "active",
      crops: toList(crops[u.id]).filter((c) => c.planted && c.harvest).map(derive),
      activities: toList(acts[u.id]).map((a) => ({ date: a.date, text: a.text })),
      harvests: toList(harvests[u.id]).map((h) => ({ date: h.date, crop: h.crop, yield_t: Number(h.yield_t) || 0, price: Number(h.price) || 0 })),
      markets: Object.keys(saved[u.id] || {}),
    }));
    const marketInfo = {};
    markets.forEach((m) => { marketInfo[m.id] = { name: m.name, town: m.town, type: m.type }; });

    window.AGRITRACK_ON_EDIT = (id, patch) => updateUser(id, patch);
    bootClassic({ farmers, markets: marketInfo }, "../assets/js/farmers.js", "AGRITRACK_FARMERS");
  } catch (e) {
    console.error(e);
    showError("Could not load farmers. " + (e.code === "PERMISSION_DENIED" ? "Publish database.rules.json in Firebase and make sure your account has role admin." : "Check your connection."));
  }
});

$("loadSample").addEventListener("click", async (e) => {
  e.target.disabled = true;
  try { const n = await seedSample(); toast(n + " sample farmers added"); setTimeout(() => location.reload(), 700); }
  catch (ex) { showError("Could not add sample data. " + (ex.code || ex.message)); e.target.disabled = false; }
});
$("clearSample").addEventListener("click", async (e) => {
  if (!confirm("Remove all sample farmers and their crops, harvests and activities? Real farmers are not touched.")) return;
  e.target.disabled = true;
  try { const n = await clearSample(); toast(n + " sample farmers removed"); setTimeout(() => location.reload(), 700); }
  catch (ex) { showError("Could not remove sample data. " + (ex.code || ex.message)); e.target.disabled = false; }
});