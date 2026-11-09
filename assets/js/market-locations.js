// assets/js/market-locations.js
// Market locations page, connected to Firebase Realtime Database.
// Reads the signed-in farmer's crops (crops/{uid}), saved markets (savedMarkets/{uid})
// and the shared market listings (markets/{id}, or the built-in defaults),
// then hands them to markets.js (the map, filters and ranking).
// Loaded by user/market-locations.php.
import { requireUser, listCrops, loadMarkets, getSaved, setSaved, bootClassic, showError, toast } from "./db.js";

const $ = (id) => document.getElementById(id);

requireUser().then(async ({ uid }) => {
  try {
    const [allCrops, markets, saved] = await Promise.all([listCrops(uid), loadMarkets(), getSaved(uid)]);

    // Only crops that are still in the field can be sold. The price lookup key is the lowercase crop name.
    const crops = allCrops
      .filter((c) => c.status !== "done")
      .sort((a, b) => (a.harvest < b.harvest ? -1 : 1))
      .map((c) => ({
        id: c.id,
        key: String(c.name || "").trim().toLowerCase(),
        name: c.name,
        field: c.field,
        yield_t: c.yield_t,
        harvest: c.harvest,
      }));

    if (!crops.length) {
      $("mlBody").hidden = true;
      $("noCrops").style.display = "block";
      return;
    }

    // ?crop=<id> preselects a crop; otherwise the one harvested first
    const wanted = new URLSearchParams(location.search).get("crop");
    const defaultCrop = (crops.find((c) => c.id === wanted) || crops[0]).id;

    window.AGRITRACK_ON_SAVE = (marketId, isSaved) => {
      setSaved(uid, marketId, isSaved).catch((err) => {
        console.error(err);
        toast("Could not save that market");
      });
    };

    bootClassic({ markets, crops, saved, defaultCrop }, "../assets/js/markets.js");
  } catch (err) {
    console.error(err);
    showError("Could not load markets. " + (err && err.code === "PERMISSION_DENIED" ? "Publish database.rules.json in Firebase first." : "Check your connection and reload."));
  }
});