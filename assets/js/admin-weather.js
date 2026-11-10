// assets/js/admin-weather.js
// Admin weather page: shows the weather in every town where registered farmers farm.
// Click a town to load its full forecast in the main card above.

import { requireUser, listAllUsers, TOWN_COORDS, esc } from './db.js';
import { describe, icon } from './weather.js';

const $ = (id) => document.getElementById(id);

async function fetchMany(towns) {
  const p = new URLSearchParams({
    latitude: towns.map((t) => t.lat).join(','),
    longitude: towns.map((t) => t.lon).join(','),
    current: 'temperature_2m,weather_code',
    daily: 'precipitation_probability_max',
    timezone: 'auto',
    forecast_days: 1,
  });
  const res = await fetch('https://api.open-meteo.com/v1/forecast?' + p);
  if (!res.ok) throw new Error('Forecast failed');
  const data = await res.json();
  return Array.isArray(data) ? data : [data];
}

async function init() {
  const sub = $('areasSub');
  const grid = $('areasGrid');
  if (!grid) return;
  await requireUser({ role: 'admin' });

  let users;
  try {
    users = await listAllUsers();
  } catch (e) {
    sub.textContent = 'Could not load farmers.';
    return;
  }

  // Group active farmers by town
  const byTown = new Map();
  for (const u of users) {
    if (u.role === 'admin' || u.status === 'inactive') continue;
    const t = u.municipality;
    if (!t || !TOWN_COORDS[t]) continue;
    byTown.set(t, (byTown.get(t) || 0) + 1);
  }
  const towns = [...byTown.entries()]
    .map(([name, count]) => ({ name, count, lat: TOWN_COORDS[name][0], lon: TOWN_COORDS[name][1] }))
    .sort((a, b) => b.count - a.count || a.name.localeCompare(b.name));

  if (!towns.length) {
    sub.textContent = 'No farmer has a farm location yet.';
    grid.innerHTML = '<p class="wx-areas-empty">Farmers pick their municipality when they register. Their areas will show up here.</p>';
    return;
  }

  let results = null;
  try { results = await fetchMany(towns); } catch (e) { /* cards still work, without live numbers */ }

  const total = towns.reduce((n, t) => n + t.count, 0);
  sub.textContent = total + (total === 1 ? ' active farmer' : ' active farmers') + ' in ' + towns.length +
    (towns.length === 1 ? ' town' : ' towns') + '. Click a town to see its full forecast.';

  grid.innerHTML = '';
  towns.forEach((t, i) => {
    const r = results && results[i];
    let body = '<span class="wx-a-cond">Forecast unavailable</span>';
    let wet = 0;
    if (r && r.current) {
      const [label, key] = describe(r.current.weather_code);
      const rain = (r.daily && r.daily.precipitation_probability_max && r.daily.precipitation_probability_max[0]) || 0;
      wet = rain >= 60 ? 1 : 0;
      body = '<span class="wx-a-temp">' + icon(key) + Math.round(r.current.temperature_2m) + '&deg;</span>' +
        '<span class="wx-a-cond">' + esc(label) + '</span>' +
        '<span class="wx-a-rain">' + rain + '% chance of rain</span>';
    }
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'wx-area';
    btn.dataset.wet = wet;
    btn.innerHTML = '<b>' + esc(t.name) + '</b><small>' + t.count + (t.count === 1 ? ' farmer' : ' farmers') + '</small>' + body;
    btn.addEventListener('click', () => {
      window.dispatchEvent(new CustomEvent('agritrack:show-place', {
        detail: { name: t.name + ', Laguna, Philippines', lat: t.lat, lon: t.lon },
      }));
    });
    grid.appendChild(btn);
  });
}

init();