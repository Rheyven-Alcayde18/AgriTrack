// assets/js/admin-crop-monitoring.js
// Admin > Crop monitoring: stage, moisture and harvest timing of every crop. Read-only.
import { requireUser, esc, fmtFull, daysText, STAGES, signOutAndGo, explain, showError } from './db.js';
import { loadAdminData } from './admin-data.js';

const $ = (id) => document.getElementById(id);
let crops = [];
let view = 'all';

const pillClass = (c) => (c.status === 'warn' ? 'warn' : c.status === 'soon' ? 'soon' : c.status === 'done' ? 'done' : 'ok');

function bars(el, rows, empty) {
  const max = Math.max(1, ...rows.map((r) => r[1]));
  el.innerHTML = rows.length && rows.some((r) => r[1] > 0)
    ? rows.map((r) => '<div class="bar-row"><span>' + esc(r[0]) + '</span><div class="bar-track"><div class="bar-fill" style="width:' + Math.round((r[1] / max) * 100) + '%"></div></div><b>' + r[1] + '</b></div>').join('')
    : '<p class="empty">' + empty + '</p>';
}

function visible() {
  const q = $('searchInput').value.trim().toLowerCase();
  const tn = $('townFilter').value;
  return crops
    .filter((c) => {
      if (view === 'all' ? c.status === 'done' : c.status !== view) return false;
      if (tn !== 'all' && c.municipality !== tn) return false;
      return !q || [c.name, c.field, c.farmer, c.municipality].join(' ').toLowerCase().indexOf(q) >= 0;
    })
    .sort((a, b) => a.days - b.days);
}

function render() {
  const field = crops.filter((c) => c.status !== 'done');
  $('sGrowing').textContent = field.length;
  $('sWater').textContent = crops.filter((c) => c.status === 'warn').length;
  $('sSoon').textContent = field.filter((c) => c.days <= 14).length;
  const wet = field.filter((c) => c.moisture > 0);
  $('sMoist').textContent = wet.length ? Math.round(wet.reduce((n, c) => n + c.moisture, 0) / wet.length) + '%' : '-';

  bars($('stageBars'), STAGES.map((s) => [s, field.filter((c) => c.stage === s).length]), 'No crops are growing yet.');
  const types = {};
  field.forEach((c) => { const k = c.name || 'Other'; types[k] = (types[k] || 0) + 1; });
  bars($('typeBars'), Object.entries(types).sort((a, b) => b[1] - a[1]).slice(0, 6), 'No crops are growing yet.');

  const list = visible();
  if (!list.length) {
    $('rows').innerHTML = '<tr><td colspan="9"><div class="state"><i class="ph-duotone ph-plant" aria-hidden="true"></i><b>' +
      (crops.length ? 'No crops here' : 'No crops yet') + '</b>' +
      (crops.length ? 'Nothing matches this view and search.' : 'Crops appear here as soon as farmers register them.') + '</div></td></tr>';
    $('foot').textContent = '';
    return;
  }
  $('rows').innerHTML = list.map((c) =>
    '<tr>' +
    '<td class="first"><div class="stack"><b>' + esc(c.name || 'Crop') + '</b><small>' + esc(c.field || '') + '</small></div></td>' +
    '<td data-label="Farmer">' + esc(c.farmer) + '</td>' +
    '<td data-label="Location">' + esc(c.municipality || 'Not set') + '</td>' +
    '<td class="nw" data-label="Stage">' + esc(c.stage) + '</td>' +
    '<td class="progress-cell" data-label="Progress"><div class="mini"><i style="width:' + c.progress + '%"></i></div><small>' + c.progress + '%</small></td>' +
    '<td class="nw" data-label="Harvest"><div class="stack"><b>' + esc(c.status === 'done' ? 'Done' : daysText(c.days)) + '</b><small>' + esc(c.harvest ? fmtFull(c.harvest) : '') + '</small></div></td>' +
    '<td class="right" data-label="Moisture">' + (c.moisture ? '<span class="' + (c.moisture < 35 ? 'pill inactive' : '') + '">' + c.moisture + '%</span>' : '-') + '</td>' +
    '<td data-label="Status"><span class="pill ' + pillClass(c) + '">' + esc(c.label) + '</span></td>' +
    '<td class="wide muted" data-label="Suggested action">' + esc(c.action || '') + '</td>' +
    '</tr>').join('');
  $('foot').textContent = 'Showing ' + list.length + ' crop' + (list.length === 1 ? '' : 's') + '.';
}

async function load() {
  $('refreshBtn').disabled = true;
  try {
    crops = (await loadAdminData()).crops;
    const keep = $('townFilter').value;
    const towns = Array.from(new Set(crops.map((c) => c.municipality).filter(Boolean))).sort();
    $('townFilter').innerHTML = '<option value="all">All municipalities</option>' + towns.map((t) => '<option value="' + esc(t) + '">' + esc(t) + '</option>').join('');
    if (towns.indexOf(keep) >= 0) $('townFilter').value = keep;
    render();
  } catch (e) {
    console.error(e);
    showError(explain(e, 'Could not load the crops.'));
    $('rows').innerHTML = '<tr><td colspan="9"><div class="state"><i class="ph-duotone ph-warning-circle" aria-hidden="true"></i><b>Could not load crops</b>Check the message above, then press Refresh.</div></td></tr>';
  } finally {
    $('refreshBtn').disabled = false;
  }
}

document.querySelector('.tabs').addEventListener('click', (e) => {
  const t = e.target.closest('[data-view]');
  if (!t) return;
  view = t.dataset.view;
  document.querySelectorAll('.tab').forEach((x) => x.setAttribute('aria-selected', x === t ? 'true' : 'false'));
  render();
});
['searchInput', 'townFilter'].forEach((id) => $(id).addEventListener('input', render));
$('refreshBtn').addEventListener('click', load);
if ($('logoutBtn')) $('logoutBtn').addEventListener('click', signOutAndGo);
requireUser({ role: 'admin' }).then(load);
