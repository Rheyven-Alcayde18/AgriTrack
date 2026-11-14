// assets/js/admin-markets.js
// Admin > Market locations: the market listings, a map, and how many farmers saved each one. Read-only.
import { requireUser, loadMarkets, listAll, esc, peso, signOutAndGo, explain, showError } from './db.js';

const $ = (id) => document.getElementById(id);
const TYPES = { public: 'Public market', coop: 'Co-op / buying station', wholesale: 'Wholesale hub' };
const TYPE_PILL = { public: 'ok', coop: 'warn', wholesale: 'soon' };
const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

let markets = [];
let savedCount = {};
let map = null;
let layer = null;
const pins = {};

const cap = (s) => String(s).charAt(0).toUpperCase() + String(s).slice(1);
function openText(m) {
  const d = Array.isArray(m.days) ? m.days : [];
  const days = d.length >= 7 ? 'Daily' : d.length ? d.map((x) => DAYS[x]).join(', ') : '';
  return [days, m.from && m.to ? m.from + ' - ' + m.to : ''].filter(Boolean).join(' · ') || '-';
}
function pricesOf(m) {
  return Object.entries(m.prices || {}).map(([crop, p]) => '<span>' + esc(cap(crop)) + ' ' + esc(peso(p)) + '</span>').join('');
}

function visible() {
  const q = $('searchInput').value.trim().toLowerCase();
  const type = $('typeFilter').value;
  return markets.filter((m) => {
    if (type !== 'all' && m.type !== type) return false;
    return !q || [m.name, m.town, m.note, Object.keys(m.prices || {}).join(' ')].join(' ').toLowerCase().indexOf(q) >= 0;
  });
}

function drawMap(list) {
  if (!window.L) { $('map').innerHTML = '<div class="state"><b>The map could not load</b>Check your internet connection.</div>'; return; }
  if (!map) {
    map = L.map('map', { scrollWheelZoom: false }).setView([14.2, 121.25], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
  }
  if (layer) layer.remove();
  layer = L.layerGroup().addTo(map);
  const pts = [];
  list.forEach((m) => {
    if (typeof m.lat !== 'number' || typeof m.lon !== 'number') return;
    const pin = L.marker([m.lat, m.lon]).addTo(layer)
      .bindPopup('<b>' + esc(m.name) + '</b><br>' + esc(m.town || '') + '<br>' + esc(openText(m)));
    pins[m.id] = pin;
    pts.push([m.lat, m.lon]);
  });
  if (pts.length) map.fitBounds(pts, { padding: [30, 30], maxZoom: 12 });
  setTimeout(() => map.invalidateSize(), 50);
}

function render() {
  $('sTotal').textContent = markets.length;
  $('sPublic').textContent = markets.filter((m) => m.type === 'public').length;
  $('sCoop').textContent = markets.filter((m) => m.type === 'coop').length;
  $('sWholesale').textContent = markets.filter((m) => m.type === 'wholesale').length;
  $('sSaved').textContent = savedFarmers;

  const list = visible();
  drawMap(list);
  if (!list.length) {
    $('rows').innerHTML = '<tr><td colspan="6"><div class="state"><i class="ph-duotone ph-map-pin" aria-hidden="true"></i><b>' +
      (markets.length ? 'No markets match your search' : 'No markets yet') + '</b>' +
      (markets.length ? 'Try a different name, town or type.' : 'Use Settings > Data tools > Add markets to load the default listings.') + '</div></td></tr>';
    $('foot').textContent = '';
    return;
  }
  $('rows').innerHTML = list.map((m) =>
    '<tr class="mk-row" data-id="' + esc(m.id) + '">' +
    '<td class="first"><div class="stack"><b>' + esc(m.name) + '</b><small>' + esc(m.note || '') + '</small></div></td>' +
    '<td data-label="Type"><span class="pill ' + (TYPE_PILL[m.type] || 'done') + '">' + esc(TYPES[m.type] || m.type || 'Market') + '</span></td>' +
    '<td data-label="Town">' + esc(m.town || '-') + '</td>' +
    '<td data-label="Open">' + esc(openText(m)) + '</td>' +
    '<td class="wide" data-label="Pays per kg"><div class="mk-prices">' + (pricesOf(m) || '<span class="muted">-</span>') + '</div></td>' +
    '<td class="right" data-label="Saved by">' + (savedCount[m.id] || 0) + '</td>' +
    '</tr>').join('');
  $('foot').textContent = 'Showing ' + list.length + ' of ' + markets.length + ' market' + (markets.length === 1 ? '' : 's') + '. Click a row to find it on the map.';
}

let savedFarmers = 0;

async function load() {
  $('refreshBtn').disabled = true;
  try {
    const [list, saved] = await Promise.all([loadMarkets(), listAll('savedMarkets')]);
    markets = list;
    savedCount = {};
    savedFarmers = 0;
    Object.values(saved || {}).forEach((byUser) => {
      const ids = Object.keys(byUser || {}).filter((k) => byUser[k]);
      if (ids.length) savedFarmers++;
      ids.forEach((id) => { savedCount[id] = (savedCount[id] || 0) + 1; });
    });
    render();
  } catch (e) {
    console.error(e);
    showError(explain(e, 'Could not load the markets.'));
    $('rows').innerHTML = '<tr><td colspan="6"><div class="state"><i class="ph-duotone ph-warning-circle" aria-hidden="true"></i><b>Could not load markets</b>Check the message above, then press Refresh.</div></td></tr>';
  } finally {
    $('refreshBtn').disabled = false;
  }
}

$('rows').addEventListener('click', (e) => {
  const tr = e.target.closest('[data-id]');
  const pin = tr && pins[tr.dataset.id];
  if (pin && map) {
    $('map').scrollIntoView({ behavior: 'smooth', block: 'center' });
    map.setView(pin.getLatLng(), 13);
    pin.openPopup();
  }
});
['searchInput', 'typeFilter'].forEach((id) => $(id).addEventListener('input', render));
$('refreshBtn').addEventListener('click', load);
if ($('logoutBtn')) $('logoutBtn').addEventListener('click', signOutAndGo);
requireUser({ role: 'admin' }).then(load);
