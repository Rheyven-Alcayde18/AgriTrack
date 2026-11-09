// assets/js/farmers.js
// Admin > Manage farmers: search, filter, profile drawer (crops, activities, harvests, saved markets),
// edit farmer information and activate / deactivate accounts.
// Loaded by admin/manage-farmers.php, which first sets window.AGRITRACK_FARMERS (farmers + markets) from PHP.
// Edits are kept in localStorage for now. Replace save() with a Firestore/database write when it is connected.
(function () {
  var DATA = window.AGRITRACK_FARMERS || { farmers: [], markets: {} };
  var EDIT_KEY = 'agritrack.farmerEdits';
  var TYPE_LABEL = { public: 'Public market', wholesale: 'Wholesale', coop: 'Cooperative' };
  var STATUS_LABEL = { ok: 'On track', soon: 'Harvest soon', warn: 'Needs attention', done: 'Harvested' };

  var $ = function (id) { return document.getElementById(id); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var peso = function (n) { return '₱' + Math.round(n).toLocaleString('en-US'); };
  var num = function (n) { return String(Math.round(n * 10) / 10); };

  var TODAY = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Manila' }).format(new Date());
  function dayDiff(a, b) { return Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000); }
  function fmt(iso) { return new Date(iso + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }
  function ago(iso) {
    var d = dayDiff(iso, TODAY);
    if (d <= 0) return 'Today';
    if (d === 1) return 'Yesterday';
    if (d < 30) return d + ' days ago';
    if (d < 365) return Math.round(d / 30) + ' mo ago';
    return Math.round(d / 365) + ' yr ago';
  }
  function initials(name) {
    return name.split(/\s+/).filter(Boolean).slice(0, 2).map(function (w) { return w.charAt(0).toUpperCase(); }).join('');
  }
  function progress(c) {
    var total = Math.max(1, dayDiff(c.planted, c.harvest));
    return Math.max(0, Math.min(100, Math.round(dayDiff(c.planted, TODAY) / total * 100)));
  }

  /* ---------- Data and edits ---------- */
  var edits = {};
  try { edits = JSON.parse(localStorage.getItem(EDIT_KEY)) || {}; } catch (e) {}
  function persist() { try { localStorage.setItem(EDIT_KEY, JSON.stringify(edits)); } catch (e) {} }

  function merged(f) {
    var o = edits[f.id];
    if (!o) return f;
    var m = {}, k;
    for (k in f) m[k] = f[k];
    for (k in o) m[k] = o[k];
    return m;
  }
  function all() { return DATA.farmers.map(merged); }
  function byId(id) { return all().filter(function (f) { return f.id === id; })[0]; }

  var state = { q: '', status: 'all', town: 'all', crop: 'all', sort: 'name', selected: null, tab: 'overview', editing: false, msg: '', lastFocus: null };

  /* ---------- List ---------- */
  function visible() {
    var q = state.q.trim().toLowerCase();
    return all().filter(function (f) {
      if (state.status !== 'all' && f.status !== state.status) return false;
      if (state.town !== 'all' && f.municipality !== state.town) return false;
      if (state.crop !== 'all' && !f.crops.some(function (c) { return c.name === state.crop; })) return false;
      if (q && (f.name + ' ' + f.email + ' ' + f.phone + ' ' + f.barangay + ' ' + f.municipality).toLowerCase().indexOf(q) === -1) return false;
      return true;
    }).sort(function (a, b) {
      if (state.sort === 'new') return b.joined.localeCompare(a.joined);
      if (state.sort === 'size') return b.farm_size - a.farm_size;
      if (state.sort === 'active') return b.last_active.localeCompare(a.last_active);
      return a.name.localeCompare(b.name);
    });
  }

  function renderStats() {
    var list = all();
    var active = list.filter(function (f) { return f.status === 'active'; }).length;
    var area = list.reduce(function (s, f) { return s + f.farm_size; }, 0);
    var fresh = list.filter(function (f) { return dayDiff(f.joined, TODAY) <= 90; }).length;
    var cells = [
      ['Registered farmers', String(list.length), fresh + ' joined in the last 90 days'],
      ['Active accounts', String(active), Math.round(active / Math.max(1, list.length) * 100) + '% of farmers'],
      ['Inactive accounts', String(list.length - active), 'Deactivated or dormant'],
      ['Total farm area', num(area) + ' ha', 'Across all farmers']
    ];
    $('stats').innerHTML = cells.map(function (c) {
      return '<div class="mf-stat"><small>' + esc(c[0]) + '</small><b>' + esc(c[1]) + '</b><span>' + esc(c[2]) + '</span></div>';
    }).join('');
  }

  function renderFilters() {
    var list = all();
    var counts = { all: list.length, active: 0, inactive: 0 };
    list.forEach(function (f) { counts[f.status]++; });
    $('status').innerHTML = [['all', 'All'], ['active', 'Active'], ['inactive', 'Inactive']].map(function (p) {
      return '<button type="button" class="mf-pill' + (state.status === p[0] ? ' active' : '') + '" data-status="' + p[0] + '">' + p[1] + '<i>' + counts[p[0]] + '</i></button>';
    }).join('');

    var towns = {}, crops = {};
    list.forEach(function (f) { towns[f.municipality] = 1; f.crops.forEach(function (c) { crops[c.name] = 1; }); });
    var opt = function (map, cur, all) {
      return '<option value="all">' + all + '</option>' + Object.keys(map).sort().map(function (k) {
        return '<option value="' + esc(k) + '"' + (k === cur ? ' selected' : '') + '>' + esc(k) + '</option>';
      }).join('');
    };
    $('town').innerHTML = opt(towns, state.town, 'All municipalities');
    $('crop').innerHTML = opt(crops, state.crop, 'All crops');
  }

  function renderRows() {
    var list = visible();
    $('count').textContent = 'Showing ' + list.length + ' of ' + DATA.farmers.length + ' farmers';
    $('empty').style.display = list.length ? 'none' : 'block';
    $('rows').innerHTML = list.map(function (f) {
      var on = f.status === 'active';
      return '<tr class="' + (on ? '' : 'off') + '" data-open="' + esc(f.id) + '" tabindex="0">' +
        '<td><div class="mf-who"><span class="mf-av">' + esc(initials(f.name)) + '</span><div><b>' + esc(f.name) + '</b><small>' + esc(f.email) + '</small></div></div></td>' +
        '<td>' + esc(f.municipality) + '<br><small style="color:var(--muted)">Brgy. ' + esc(f.barangay) + '</small></td>' +
        '<td><div class="mf-chips">' + (f.crops.length ? f.crops.map(function (c) { return '<span class="mf-chip">' + esc(c.name) + '</span>'; }).join('') : '<span class="mf-chip">None</span>') + '</div></td>' +
        '<td>' + num(f.farm_size) + ' ha</td>' +
        '<td><span class="mf-tag ' + f.status + '">' + (on ? 'Active' : 'Inactive') + '</span></td>' +
        '<td>' + esc(ago(f.last_active)) + '</td>' +
        '<td class="mf-act"><button type="button" class="mf-btn" data-open="' + esc(f.id) + '">View profile</button></td>' +
      '</tr>';
    }).join('');
  }

  function renderList() { renderStats(); renderFilters(); renderRows(); }

  /* ---------- Profile drawer ---------- */
  function tabOverview(f) {
    var totalT = f.harvests.reduce(function (s, h) { return s + h.yield_t; }, 0);
    var revenue = f.harvests.reduce(function (s, h) { return s + h.yield_t * 1000 * h.price; }, 0);
    var kv = function (k, v) { return '<div><small>' + esc(k) + '</small><b>' + esc(v) + '</b></div>'; };
    return (state.msg ? '<div class="mf-banner good">' + esc(state.msg) + '</div>' : '') +
      (f.status === 'inactive' ? '<div class="mf-banner">This account is deactivated. The farmer cannot sign in until it is activated again.</div>' : '') +
      '<div class="mf-mini">' +
        '<div><small>Crops</small><b>' + f.crops.length + '</b></div>' +
        '<div><small>Harvested</small><b>' + num(totalT) + ' t</b></div>' +
        '<div><small>Revenue</small><b>' + peso(revenue) + '</b></div>' +
      '</div>' +
      '<h3>Contact and farm</h3>' +
      '<div class="mf-kv">' +
        kv('Email', f.email) + kv('Phone', f.phone) +
        kv('Municipality', f.municipality) + kv('Barangay', f.barangay) +
        kv('Farm size', num(f.farm_size) + ' ha') + kv('Member since', fmt(f.joined)) +
        kv('Last active', ago(f.last_active)) + kv('Account', f.status === 'active' ? 'Active' : 'Inactive') +
      '</div>';
  }

  function tabCrops(f) {
    if (!f.crops.length) return '<div class="mf-none">No crops registered yet.</div>';
    return f.crops.map(function (c) {
      var p = c.status === 'done' ? 100 : progress(c), left = dayDiff(TODAY, c.harvest);
      var when = c.status === 'done' ? 'Harvested' : (left > 0 ? 'Harvest in ' + left + ' day' + (left === 1 ? '' : 's') : (left === 0 ? 'Harvest today' : 'Ready to harvest'));
      return '<div class="mf-item"><div class="mf-item-top"><div><b>' + esc(c.name) + '</b><br><small>' + esc(c.field) + ' &middot; ' + num(c.area) + ' ha &middot; ' + esc(c.stage) + '</small></div>' +
        '<span class="mf-tag ' + c.status + '">' + esc(STATUS_LABEL[c.status] || c.status) + '</span></div>' +
        '<div class="mf-bar"><div class="tr"><i style="width:' + p + '%"></i></div><span>' + p + '%</span></div>' +
        '<div class="mf-dates"><span>Planted ' + esc(fmt(c.planted)) + '</span><span>Harvest ' + esc(fmt(c.harvest)) + '</span><span>' + esc(when) + '</span></div></div>';
    }).join('');
  }

  function tabActivities(f) {
    if (!f.activities.length) return '<div class="mf-none">No activity recorded yet.</div>';
    return '<ul class="mf-time">' + f.activities.slice().sort(function (a, b) { return b.date.localeCompare(a.date); }).map(function (a) {
      return '<li><small>' + esc(fmt(a.date)) + ' &middot; ' + esc(ago(a.date)) + '</small>' + esc(a.text) + '</li>';
    }).join('') + '</ul>';
  }

  function tabHarvests(f) {
    if (!f.harvests.length) return '<div class="mf-none">No completed harvests yet.</div>';
    var list = f.harvests.slice().sort(function (a, b) { return b.date.localeCompare(a.date); });
    var totalT = 0, totalR = 0;
    var rows = list.map(function (h) {
      var rev = h.yield_t * 1000 * h.price; totalT += h.yield_t; totalR += rev;
      return '<tr><td>' + esc(fmt(h.date)) + '</td><td><b>' + esc(h.crop) + '</b></td><td class="r">' + num(h.yield_t) + ' t</td><td class="r">' + peso(h.price) + '/kg</td><td class="r">' + peso(rev) + '</td></tr>';
    }).join('');
    return '<table class="mf-htable"><thead><tr><th>Date</th><th>Crop</th><th class="r">Yield</th><th class="r">Price</th><th class="r">Revenue</th></tr></thead><tbody>' + rows +
      '</tbody><tfoot><tr><td colspan="2">Total</td><td class="r">' + num(totalT) + ' t</td><td></td><td class="r">' + peso(totalR) + '</td></tr></tfoot></table>';
  }

  function tabMarkets(f) {
    if (!f.markets.length) return '<div class="mf-none">This farmer has not saved any markets.</div>';
    return f.markets.map(function (id) {
      var m = DATA.markets[id];
      if (!m) return '';
      return '<div class="mf-item"><div class="mf-item-top"><div><b>' + esc(m.name) + '</b><br><small>' + esc(m.town) + '</small></div><span class="mf-chip">' + esc(TYPE_LABEL[m.type] || m.type) + '</span></div></div>';
    }).join('');
  }

  function editForm(f) {
    var field = function (id, label, val, type, extra) {
      return '<div class="mf-field"><label for="e_' + id + '">' + label + '</label><input id="e_' + id + '" type="' + (type || 'text') + '" value="' + esc(val) + '" ' + (extra || '') + '><div class="mf-err" id="err_' + id + '"></div></div>';
    };
    return '<h3>Edit farmer information</h3><form class="mf-form" id="editForm" novalidate>' +
      field('name', 'Full name', f.name, 'text', 'autocomplete="off"') +
      '<div class="two">' + field('phone', 'Phone', f.phone, 'tel') + field('farm_size', 'Farm size (ha)', f.farm_size, 'number', 'min="0.1" step="0.1"') + '</div>' +
      '<div class="two">' + field('municipality', 'Municipality', f.municipality) + field('barangay', 'Barangay', f.barangay) + '</div>' +
      '<div class="mf-field"><label>Email</label><input type="email" value="' + esc(f.email) + '" disabled><div class="mf-err" style="color:var(--muted)">The email is the farmer’s sign-in and cannot be changed here.</div></div>' +
      '<div class="mf-form-actions"><button type="submit" class="mf-btn primary">Save changes</button><button type="button" class="mf-btn" data-cancel="1">Cancel</button></div></form>';
  }

  function renderDrawer() {
    var f = byId(state.selected);
    if (!f) return;
    var on = f.status === 'active';
    var tabs = [['overview', 'Overview'], ['crops', 'Crops (' + f.crops.length + ')'], ['activities', 'Activities'], ['harvests', 'Harvests (' + f.harvests.length + ')'], ['markets', 'Saved markets (' + f.markets.length + ')']];
    var body = state.editing ? editForm(f) : ({ overview: tabOverview, crops: tabCrops, activities: tabActivities, harvests: tabHarvests, markets: tabMarkets }[state.tab])(f);
    $('drawer').innerHTML =
      '<div class="mf-d-head"><div class="mf-d-top"><span class="mf-av">' + esc(initials(f.name)) + '</span>' +
        '<div><h2 id="dName">' + esc(f.name) + '</h2><p>' + esc(f.municipality) + ', Laguna &middot; <span class="mf-tag ' + f.status + '">' + (on ? 'Active' : 'Inactive') + '</span></p></div>' +
        '<button type="button" class="mf-close" data-close="1" aria-label="Close profile">&times;</button></div>' +
        '<div class="mf-d-actions"><button type="button" class="mf-btn primary" data-edit="1">Edit information</button>' +
        '<button type="button" class="mf-btn ' + (on ? 'danger' : '') + '" data-toggle="' + esc(f.id) + '">' + (on ? 'Deactivate account' : 'Activate account') + '</button></div>' +
        '<div class="mf-tabs" role="tablist">' + tabs.map(function (t) {
          return '<button type="button" role="tab" class="mf-tab' + (!state.editing && state.tab === t[0] ? ' active' : '') + '" data-tab="' + t[0] + '">' + esc(t[1]) + '</button>';
        }).join('') + '</div></div>' +
      '<div class="mf-d-body">' + body + '</div>';
  }

  function openProfile(id, opener) {
    state.selected = id; state.tab = 'overview'; state.editing = false; state.msg = '';
    state.lastFocus = opener || null;
    renderDrawer();
    $('overlay').classList.add('open'); $('drawer').classList.add('open');
    document.body.style.overflow = 'hidden';
    $('drawer').focus();
  }
  function closeProfile() {
    state.selected = null; state.editing = false;
    $('overlay').classList.remove('open'); $('drawer').classList.remove('open');
    document.body.style.overflow = '';
    if (state.lastFocus && state.lastFocus.focus) state.lastFocus.focus();
  }

  function toggleStatus(id) {
    var f = byId(id), next = f.status === 'active' ? 'inactive' : 'active';
    var ok = window.confirm(next === 'inactive'
      ? 'Deactivate ' + f.name + '? They will not be able to sign in until you activate the account again.'
      : 'Activate ' + f.name + '’s account?');
    if (!ok) return;
    edits[id] = edits[id] || {};
    edits[id].status = next;
    persist();
    state.msg = next === 'active' ? 'Account activated.' : 'Account deactivated.';
    state.tab = 'overview'; state.editing = false;
    renderDrawer(); renderList();
  }

  function saveEdit(form) {
    var f = byId(state.selected), v = {}, bad = false;
    ['name', 'phone', 'municipality', 'barangay', 'farm_size'].forEach(function (k) { v[k] = (form.querySelector('#e_' + k).value || '').trim(); $('err_' + k).textContent = ''; });
    var fail = function (k, m) { $('err_' + k).textContent = m; bad = true; };
    if (v.name.length < 2) fail('name', 'Enter the farmer’s full name.');
    if (v.phone && !/^[0-9+()\-\s]{7,20}$/.test(v.phone)) fail('phone', 'Use digits, spaces, + or - only.');
    if (!v.municipality) fail('municipality', 'Enter a municipality.');
    if (!v.barangay) fail('barangay', 'Enter a barangay.');
    var size = parseFloat(v.farm_size);
    if (!isFinite(size) || size <= 0 || size > 1000) fail('farm_size', 'Enter a size above 0 and up to 1000 ha.');
    if (bad) return;
    edits[f.id] = edits[f.id] || {};
    edits[f.id].name = v.name; edits[f.id].phone = v.phone; edits[f.id].municipality = v.municipality;
    edits[f.id].barangay = v.barangay; edits[f.id].farm_size = size;
    persist();
    state.editing = false; state.tab = 'overview'; state.msg = 'Changes saved.';
    renderDrawer(); renderList();
  }

  /* ---------- Events ---------- */
  $('q').addEventListener('input', function () { state.q = this.value; renderRows(); });
  $('town').addEventListener('change', function () { state.town = this.value; renderRows(); });
  $('crop').addEventListener('change', function () { state.crop = this.value; renderRows(); });
  $('sort').addEventListener('change', function () { state.sort = this.value; renderRows(); });
  $('status').addEventListener('click', function (e) {
    var b = e.target.closest('[data-status]'); if (!b) return;
    state.status = b.getAttribute('data-status'); renderFilters(); renderRows();
  });
  $('rows').addEventListener('click', function (e) {
    var el = e.target.closest('[data-open]'); if (el) openProfile(el.getAttribute('data-open'), el);
  });
  $('rows').addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    var el = e.target.closest('[data-open]'); if (el) openProfile(el.getAttribute('data-open'), el);
  });
  $('overlay').addEventListener('click', closeProfile);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && state.selected) closeProfile(); });

  $('drawer').addEventListener('click', function (e) {
    var t = e.target.closest('[data-tab],[data-close],[data-edit],[data-toggle],[data-cancel]');
    if (!t) return;
    if (t.hasAttribute('data-close')) return closeProfile();
    if (t.hasAttribute('data-tab')) { state.tab = t.getAttribute('data-tab'); state.editing = false; state.msg = ''; return renderDrawer(); }
    if (t.hasAttribute('data-edit')) { state.editing = true; state.msg = ''; renderDrawer(); var i = $('e_name'); if (i && i.focus) i.focus(); return; }
    if (t.hasAttribute('data-cancel')) { state.editing = false; return renderDrawer(); }
    if (t.hasAttribute('data-toggle')) return toggleStatus(t.getAttribute('data-toggle'));
  });
  $('drawer').addEventListener('submit', function (e) {
    if (e.target && e.target.id === 'editForm') { e.preventDefault(); saveEdit(e.target); }
  });

  $('resetEdits').addEventListener('click', function () {
    if (!window.confirm('Remove all edits and status changes made in this browser?')) return;
    edits = {}; persist(); state.msg = '';
    renderList(); if (state.selected) renderDrawer();
  });

  renderList();
})();