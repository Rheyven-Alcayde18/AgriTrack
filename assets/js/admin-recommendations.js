// assets/js/admin-recommendations.js
// Admin > Recommendations: a follow-up list built from the farmers' crops and profiles. Read-only.
import { requireUser, esc, dayDiff, todayISO, signOutAndGo, explain, showError } from './db.js';
import { loadAdminData, locationOf } from './admin-data.js';

const $ = (id) => document.getElementById(id);
const ORDER = { 'Urgent': 0, 'This week': 1, 'Plan ahead': 2 };
const PILL = { 'Urgent': 'inactive', 'This week': 'soon', 'Plan ahead': 'warn' };

let items = [];
let view = 'all';

// Turns the loaded data into one list of things to do.
function build(data) {
  const out = [];
  const add = (f, priority, topic, text) => out.push({ farmer: f, priority, topic, text });

  data.crops.forEach((c) => {
    const f = data.farmers.find((x) => x.id === c.uid);
    if (!f || f.status !== 'active' || c.status === 'done') return;
    const name = (c.name || 'Crop') + (c.field ? ' (' + c.field + ')' : '');
    if (c.moisture > 0 && c.moisture < 35) {
      add(f, 'Urgent', 'Watering', name + ' is dry (' + c.moisture + '% soil moisture). Remind the farmer to water it today.');
    }
    if (c.days <= 0) {
      add(f, 'Urgent', 'Harvest', name + ' is ready to harvest now. Check that transport and a buyer are lined up.');
    } else if (c.days <= 7) {
      add(f, 'This week', 'Harvest', name + ' will be ready in ' + c.days + ' day' + (c.days === 1 ? '' : 's') + '. Help the farmer plan picking and selling.');
    } else if (c.days <= 14) {
      add(f, 'Plan ahead', 'Harvest', name + ' will be ready in ' + c.days + ' days. A good time to look at market prices.');
    }
  });

  const today = todayISO();
  data.farmers.forEach((f) => {
    if (f.status !== 'active') return;
    if (!f.municipality || !f.barangay) {
      add(f, 'This week', 'Profile', 'No full farm location yet. Weather, markets and tips depend on it. Set it in Manage farmers.');
    }
    if (!f.crops.length) {
      add(f, 'Plan ahead', 'Activity', 'No crops registered yet. Ask the farmer to add the crops they are growing.');
    }
    if (f.lastActive && dayDiff(f.lastActive, today) >= 30) {
      add(f, 'Plan ahead', 'Activity', 'Has not signed in for ' + dayDiff(f.lastActive, today) + ' days. A short check-in may help.');
    }
  });

  return out.sort((a, b) => ORDER[a.priority] - ORDER[b.priority] || String(a.farmer.name).localeCompare(String(b.farmer.name)));
}

function visible() {
  const q = $('searchInput').value.trim().toLowerCase();
  const topic = $('topicFilter').value;
  return items.filter((i) => {
    if (view !== 'all' && i.priority !== view) return false;
    if (topic !== 'all' && i.topic !== topic) return false;
    return !q || [i.farmer.name, i.farmer.municipality, i.farmer.barangay, i.text].join(' ').toLowerCase().indexOf(q) >= 0;
  });
}

function render() {
  $('sUrgent').textContent = items.filter((i) => i.priority === 'Urgent').length;
  $('sWeek').textContent = items.filter((i) => i.priority === 'This week').length;
  $('sPlan').textContent = items.filter((i) => i.priority === 'Plan ahead').length;
  $('sFarmers').textContent = new Set(items.map((i) => i.farmer.id)).size;

  const list = visible();
  if (!list.length) {
    $('rows').innerHTML = '<tr><td colspan="5"><div class="state"><i class="ph-duotone ph-check-circle" aria-hidden="true"></i><b>' +
      (items.length ? 'Nothing here' : 'All clear') + '</b>' +
      (items.length ? 'No recommendations match this view and search.' : 'No farmer needs a follow-up right now.') + '</div></td></tr>';
    $('foot').textContent = '';
    return;
  }
  $('rows').innerHTML = list.map((i) =>
    '<tr>' +
    '<td class="first"><span class="pill ' + PILL[i.priority] + '">' + esc(i.priority) + '</span></td>' +
    '<td class="nw" data-label="Farmer"><b>' + esc(i.farmer.name) + '</b></td>' +
    '<td data-label="Location">' + esc(locationOf(i.farmer)) + '</td>' +
    '<td data-label="Topic">' + esc(i.topic) + '</td>' +
    '<td class="wide" data-label="What to do">' + esc(i.text) + '</td>' +
    '</tr>').join('');
  $('foot').textContent = 'Showing ' + list.length + ' of ' + items.length + ' recommendation' + (items.length === 1 ? '' : 's') + '.';
}

async function load() {
  $('refreshBtn').disabled = true;
  try {
    items = build(await loadAdminData());
    render();
  } catch (e) {
    console.error(e);
    showError(explain(e, 'Could not load the data.'));
    $('rows').innerHTML = '<tr><td colspan="5"><div class="state"><i class="ph-duotone ph-warning-circle" aria-hidden="true"></i><b>Could not load recommendations</b>Check the message above, then press Refresh.</div></td></tr>';
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
['searchInput', 'topicFilter'].forEach((id) => $(id).addEventListener('input', render));
$('refreshBtn').addEventListener('click', load);
if ($('logoutBtn')) $('logoutBtn').addEventListener('click', signOutAndGo);
requireUser({ role: 'admin' }).then(load);
