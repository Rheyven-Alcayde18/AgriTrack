// assets/js/recommendations.js
// Recommendations: reads the signed-in farmer's crops, the market listings and today's ticked items
// from Firebase (through db.js), then builds the action list from them and the Open-Meteo forecast.
// Loaded by user/recommendations.php as a module. No other loader file is needed.
import { requireUser, listCrops, loadMarkets, getChecklist, setCheck, showError, toast } from "./db.js";

requireUser().then(async ({ uid }) => {
  try {
    var all = await Promise.all([listCrops(uid), loadMarkets(), getChecklist(uid)]);

    // Only crops still in the field get recommendations. No crops added means no crop recommendations.
    var crops = all[0]
      .filter(function (c) { return c.status !== 'done'; })
      .sort(function (a, b) { return a.harvest < b.harvest ? -1 : 1; })
      .map(function (c) {
        return {
          id: c.id, key: String(c.name || '').trim().toLowerCase(), name: c.name, field: c.field, area: c.area,
          stage: c.stage, progress: c.progress, moisture: c.moisture, harvest: c.harvest, days: c.days,
          yield_t: c.yield_t, price: c.price, prep: c.prep
        };
      });

    run(
      { crops: crops, markets: all[1], done: Object.keys(all[2] || {}) },
      function (key, on) {
        setCheck(uid, key, !!on).catch(function (err) { console.error(err); toast('Could not save that tick'); });
      }
    );
  } catch (err) {
    console.error(err);
    showError('Could not load recommendations. ' + (err && err.code === 'PERMISSION_DENIED' ? 'Publish database.rules.json in Firebase first.' : 'Check your connection and reload.'));
  }
});

function run(DATA, onDone) {
  var DEFAULT_FARM = { name: 'Bay, Laguna, Philippines', lat: 14.1833, lon: 121.2833 };
  var RATE = 15; // transport cost per km used to compare markets
  var CATS = { water: 'Watering', weather: 'Weather', harvest: 'Harvest', market: 'Selling', care: 'Crop care' };
  var PRI = ['Urgent', 'This week', 'Plan ahead'];
  var DAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  var $ = function (id) { return document.getElementById(id); };
  var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var peso = function (n) { return '₱' + Math.round(n).toLocaleString('en-US'); };

  function getFarm() {
    try {
      var v = JSON.parse(localStorage.getItem('agritrack.location'));
      if (v && isFinite(v.lat) && isFinite(v.lon) && v.name) return v;
    } catch (e) {}
    return DEFAULT_FARM;
  }
  var farm = getFarm();
  $('fromName').textContent = farm.name;

  function todayKey() {
    return new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Manila' }).format(new Date());
  }
  var TODAY = todayKey();
  var done = {};
  (DATA.done || []).forEach(function (k) { if (k.split('|')[1] === TODAY) done[k] = true; }); // old days reset
  function saveDone(key, on) { onDone(key, on); }
  var isDone = function (r) { return !!done[r.id + '|' + TODAY]; };

  function kmBetween(lat1, lon1, lat2, lon2) {
    var R = 6371, rad = Math.PI / 180, dLat = (lat2 - lat1) * rad, dLon = (lon2 - lon1) * rad;
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }
  function clock(t) {
    var p = t.split(':'), h = parseInt(p[0], 10);
    return ((h + 11) % 12 + 1) + (p[1] !== '00' ? ':' + p[1] : '') + ' ' + (h >= 12 ? 'PM' : 'AM');
  }
  function describe(code) {
    if (code === 0) return 'Clear sky';
    if (code <= 2) return 'Partly cloudy';
    if (code === 3) return 'Overcast';
    if (code <= 48) return 'Foggy';
    if (code <= 57) return 'Drizzle';
    if (code <= 67) return 'Rain';
    if (code <= 77) return 'Snow';
    if (code <= 82) return 'Rain showers';
    if (code <= 86) return 'Snow showers';
    return 'Thunderstorm';
  }
  function dueText(days) {
    if (days > 0) return 'Harvest in ' + days + ' day' + (days === 1 ? '' : 's');
    return days === 0 ? 'Harvest today' : 'Ready to harvest';
  }

  /* ---------- Weather helpers (null when the forecast is unavailable) ---------- */
  var wx = null;
  function dayInfo(i) {
    var d = wx.daily, rain = d.precipitation_probability_max[i] || 0, wind = d.wind_speed_10m_max[i] || 0, code = d.weather_code[i];
    var rate = (rain >= 60 || code >= 95) ? 'avoid' : (rain >= 30 || wind >= 25 || d.temperature_2m_max[i] >= 35) ? 'caution' : 'good';
    var date = new Date(d.time[i] + 'T00:00:00');
    return { rain: rain, wind: Math.round(wind), max: Math.round(d.temperature_2m_max[i]), code: code, rate: rate,
             name: i === 0 ? 'Today' : DAY[date.getDay()], full: i === 0 ? 'today' : date.toLocaleDateString('en-US', { weekday: 'long' }) };
  }

  /* ---------- Build the recommendations ---------- */
  function build() {
    var recs = [];
    function add(r) { recs.push(r); }
    var today = wx ? dayInfo(0) : null;
    var tomorrow = wx ? dayInfo(1) : null;

    DATA.crops.forEach(function (c) {
      var link = function (page, label) { return { href: page + '#crop-' + c.id, label: label }; };

      // Watering
      if (c.moisture < 35) {
        if (today && today.rain >= 70) {
          add({ id: 'water-' + c.id, pri: 1, cat: 'water', crop: c.name, due: 'Today',
            title: c.name + ' soil is dry, but rain is likely',
            why: 'Soil moisture in ' + c.field + ' is ' + c.moisture + '%, below the healthy 35%. Rain chance today is ' + today.rain + '%.',
            steps: ['Check the field after the rain', 'Water this evening if the soil is still dry', 'Check moisture again tomorrow morning'],
            link: link('crop-monitoring.php', 'View crop') });
        } else {
          var steps = ['Irrigate in the early morning or late afternoon', 'Water slowly so it soaks in instead of running off', 'Check moisture again tomorrow'];
          if (today && today.max >= 33) steps.push('Today reaches ' + today.max + '°C, so water a little extra');
          add({ id: 'water-' + c.id, pri: 0, cat: 'water', crop: c.name, due: 'Today',
            title: 'Water ' + c.name + ' today',
            why: 'Soil moisture in ' + c.field + ' is ' + c.moisture + '%, below the healthy range of 35% or more.' + (c.stage === 'Seedling' ? ' Young seedlings dry out fastest.' : ''),
            steps: steps, link: link('crop-monitoring.php', 'View crop') });
        }
      } else if (c.moisture < 50 && c.key !== 'rice') {
        add({ id: 'watersoon-' + c.id, pri: 2, cat: 'water', crop: c.name, due: 'Next 2 days',
          title: 'Plan watering for ' + c.name,
          why: 'Soil moisture in ' + c.field + ' is ' + c.moisture + '% and drifting toward the dry range.',
          steps: ['Water within the next two days unless rain arrives', 'Mulch around plants to hold moisture'],
          link: link('crop-monitoring.php', 'View crop') });
      } else if (c.moisture > 78 && c.key !== 'rice' && wx) {
        var wet = 0; for (var k = 0; k < 3; k++) wet = Math.max(wet, dayInfo(k).rain);
        if (wet >= 60) {
          add({ id: 'drain-' + c.id, pri: 1, cat: 'water', crop: c.name, due: 'Before the rain',
            title: 'Check drainage in ' + c.field,
            why: 'The soil is already at ' + c.moisture + '% moisture and rain chance reaches ' + wet + '% in the next three days.',
            steps: ['Clear drainage channels', 'Hold off irrigation', 'Watch for standing water after rain'],
            link: link('crop-monitoring.php', 'View crop') });
        }
      }

      // Harvest preparation
      if (c.days <= 14) {
        var hp = c.days <= 3 ? 0 : (c.days <= 7 ? 1 : 2);
        var title = c.days <= 0 ? 'Harvest ' + c.name + ' now' : 'Prepare to harvest ' + c.name;
        add({ id: 'harvest-' + c.id, pri: hp, cat: 'harvest', crop: c.name, due: dueText(c.days),
          title: title,
          why: c.name + ' in ' + c.field + ' is ' + c.progress + '% grown and due on ' + c.harvest + '. Expected yield is ' + c.yield_t + ' t.',
          steps: c.prep.concat(['Check the weather the day before picking']),
          link: link('harvest-management.php', 'Open harvest plan') });

        // Rain before harvest
        if (wx && c.days <= 7) {
          var from = Math.max(c.days - 1, 0), limit = Math.min(Math.max(c.days, 0), 6), rainy = -1;
          for (var i = from; i <= limit; i++) { if (dayInfo(i).rain >= 60) { rainy = i; break; } }
          if (rainy > -1) {
            var di = dayInfo(rainy);
            add({ id: 'rainharvest-' + c.id, pri: 1, cat: 'weather', crop: c.name, due: 'Around ' + di.full,
              title: 'Rain is forecast near the ' + c.name.toLowerCase() + ' harvest',
              why: 'Rain chance reaches ' + di.rain + '% ' + (rainy === 0 ? 'today' : 'on ' + di.full) + ', and ' + c.name.toLowerCase() + ' is due ' + c.harvest + '. Wet weather can delay picking and spoil produce.',
              steps: ['Harvest on a dry day when the crop is ready', 'Move picking earlier if the crop is ready and you can', 'Keep covers and dry storage ready'],
              link: link('harvest-management.php', 'Open harvest plan') });
          }
        }
      }

      // Where to sell
      if (c.days <= 30 && c.days >= -5) {
        var rows = DATA.markets.filter(function (m) { return (m.prices || {})[c.key] != null; }).map(function (m) {
          var dist = kmBetween(farm.lat, farm.lon, m.lat, m.lon);
          return { m: m, dist: dist, price: m.prices[c.key], net: c.yield_t * 1000 * m.prices[c.key] - dist * 2 * RATE };
        }).sort(function (a, b) { return b.net - a.net; });
        if (rows.length) {
          var b1 = rows[0], b2 = rows[1];
          var st = ['Call ahead to confirm today’s price', 'Open ' + clock(b1.m.from) + ' to ' + clock(b1.m.to) + '. ' + b1.m.note];
          if (b2) st.push('Next best: ' + b2.m.name + ' at ' + peso(b2.net) + ' (' + b2.dist.toFixed(1) + ' km)');
          add({ id: 'sell-' + c.id, pri: c.days <= 7 ? 1 : 2, cat: 'market', crop: c.name, due: dueText(c.days),
            title: 'Sell ' + c.name + ' at ' + b1.m.name,
            why: 'Best estimated earnings: ' + peso(b1.net) + ' for ' + c.yield_t + ' t at ' + peso(b1.price) + '/kg, ' + b1.dist.toFixed(1) + ' km away, after round-trip transport.',
            steps: st, link: { href: 'market-locations.php?crop=' + c.id, label: 'Compare markets' } });
        }
      }

      // Crop care by growth stage
      var care = null;
      if (c.stage === 'Seedling') {
        care = { t: 'Protect ' + c.name + ' seedlings', s: ['Check for pests and weeds twice a week', 'Keep soil moist but not soaked', 'Replace failed seedlings early'] };
      } else if (c.stage === 'Vegetative') {
        var rainSoon = wx && (today.rain >= 60 || tomorrow.rain >= 60);
        care = { t: 'Feed ' + c.name + ' with fertilizer',
          s: [rainSoon ? 'Rain is likely soon, so wait for a dry day to avoid washing it away' : 'Apply when no heavy rain is expected for 24 hours',
              'Water lightly afterwards so it reaches the roots', 'Keep fertilizer off the leaves'] };
      } else if (c.stage === 'Flowering') {
        care = { t: 'Keep ' + c.name + ' water steady', s: ['Avoid water stress during flowering', 'Scout for pests', 'Avoid spraying in the midday heat'] };
      } else if (c.stage === 'Fruiting') {
        care = c.key === 'rice'
          ? { t: 'Look after ' + c.name + ' during grain filling', s: ['Keep the field moist until grain filling finishes', 'Plan to drain the field about a week before harvest', 'Watch for rice bugs and birds'] }
          : { t: 'Watch ' + c.name + ' for pests and support the fruit', s: ['Check for fruit borers and disease', 'Support heavy plants with stakes', 'Water at the base, not over the leaves'] };
      }
      if (care) {
        add({ id: 'care-' + c.id, pri: 2, cat: 'care', crop: c.name, due: 'This week',
          title: care.t,
          why: c.name + ' in ' + c.field + ' is at the ' + c.stage.toLowerCase() + ' stage (' + c.progress + '% grown).',
          steps: care.s, link: link('crop-monitoring.php', 'View crop') });
      }
    });

    // Weather for the whole farm
    if (wx) {
      var stormIdx = -1;
      for (var s = 0; s < 3; s++) { if (dayInfo(s).code >= 95) { stormIdx = s; break; } }
      if (stormIdx > -1) {
        var sd = dayInfo(stormIdx);
        add({ id: 'storm', pri: 0, cat: 'weather', crop: null, due: sd.name,
          title: 'Thunderstorms expected ' + (stormIdx === 0 ? 'today' : sd.full),
          why: 'The forecast shows thunderstorms. Open fields are unsafe during lightning.',
          steps: ['Stay out of open fields during the storm', 'Secure equipment, covers and stakes', 'Harvest anything ready beforehand'],
          link: { href: 'weather-forecast.php', label: 'See forecast' } });
      }
      if (today.max >= 34) {
        add({ id: 'heat', pri: 1, cat: 'weather', crop: null, due: 'Today',
          title: 'Hot day ahead',
          why: 'Today’s high is ' + today.max + '°C, which stresses plants and workers.',
          steps: ['Water early morning or late afternoon', 'Give workers shade and water breaks', 'Avoid spraying and fertilizing at midday'],
          link: { href: 'weather-forecast.php', label: 'See forecast' } });
      }
      var windy = -1;
      for (var w = 0; w < 3; w++) { if (dayInfo(w).wind >= 30) { windy = w; break; } }
      if (windy > -1) {
        add({ id: 'wind', pri: 1, cat: 'weather', crop: null, due: dayInfo(windy).name,
          title: 'Strong winds forecast',
          why: 'Wind could reach ' + dayInfo(windy).wind + ' km/h ' + (windy === 0 ? 'today' : 'on ' + dayInfo(windy).full) + '.',
          steps: ['Secure covers, stakes and loose equipment', 'Do not spray, since the wind will carry it away', 'Check tall plants for damage afterwards'],
          link: { href: 'weather-forecast.php', label: 'See forecast' } });
      }
      var spray = -1;
      for (var p = 0; p < 7; p++) { var q = dayInfo(p); if (q.rain < 30 && q.wind < 20) { spray = p; break; } }
      if (spray > -1) {
        var sp = dayInfo(spray);
        add({ id: 'spray', pri: 2, cat: 'weather', crop: null, due: sp.name,
          title: 'Best day to spray: ' + sp.full,
          why: 'Rain chance is ' + sp.rain + '% and wind is ' + sp.wind + ' km/h, so spray should stay on the plants.',
          steps: ['Spray in the early morning', 'Skip crops that are due for harvest soon', 'Avoid the hottest hours'],
          link: { href: 'weather-forecast.php', label: 'See forecast' } });
      } else {
        add({ id: 'nospray', pri: 2, cat: 'weather', crop: null, due: 'This week',
          title: 'No good spraying day this week',
          why: 'Every day in the forecast has rain or strong wind. Spraying now would likely wash away or drift.',
          steps: ['Delay non-urgent spraying', 'Check the forecast again tomorrow'],
          link: { href: 'weather-forecast.php', label: 'See forecast' } });
      }
    }

    var order = { water: 0, harvest: 1, weather: 2, market: 3, care: 4 };
    recs.sort(function (a, b) { return a.pri - b.pri || order[a.cat] - order[b.cat]; });
    return recs;
  }

  /* ---------- Rendering ---------- */
  var state = { cat: 'all', showDone: false };
  var recs = [];

  function renderBanner() {
    var box = $('now');
    box.classList.remove('loading');
    if (!wx) { $('nowTemp').textContent = '--'; $('nowCond').textContent = 'Forecast unavailable'; return; }
    var c = wx.current, t = dayInfo(0);
    $('nowTemp').textContent = Math.round(c.temperature_2m) + '°C';
    $('nowCond').textContent = describe(c.weather_code) + ' in ' + farm.name.split(',')[0];
    $('nowRain').textContent = t.rain + '%';
    $('nowWind').textContent = Math.round(c.wind_speed_10m) + ' km/h';
    var v = { good: 'Good', caution: 'Use caution', avoid: 'Avoid' }[t.rate];
    $('nowVerdict').textContent = v;
  }

  function renderPlan() {
    var card = $('planCard');
    if (!wx) { card.style.display = 'none'; return; }
    var html = '';
    for (var i = 0; i < 7; i++) {
      var d = dayInfo(i), label = { good: 'Good', caution: 'Caution', avoid: 'Avoid' }[d.rate];
      html += '<div class="rc-day' + (i === 0 ? ' today' : '') + '"><b>' + esc(d.name) + '</b>' +
        '<small>' + d.rain + '% rain<br>' + d.wind + ' km/h wind<br>' + d.max + '°C</small>' +
        '<span class="rc-rate ' + d.rate + '">' + label + '</span></div>';
    }
    $('days').innerHTML = html;
  }

  function renderStats() {
    var open = recs.filter(function (r) { return !isDone(r); });
    var urgent = open.filter(function (r) { return r.pri === 0; }).length;
    var week = open.filter(function (r) { return r.pri <= 1; }).length;
    var doneCount = recs.length - open.length;
    var soon = DATA.crops.slice().sort(function (a, b) { return a.days - b.days; })[0] || { name: 'None', days: null };
    var cells = [
      ['Urgent', String(urgent), urgent ? 'Do these first' : 'Nothing urgent', urgent ? ' urgent' : ''],
      ['Needs action this week', String(week), 'Urgent and this-week items', ''],
      ['Done today', doneCount + ' of ' + recs.length, 'Resets each day', ''],
      ['Next harvest', soon.name, soon.days === null ? 'Add a crop in Crop monitoring' : dueText(soon.days), '']
    ];
    $('stats').innerHTML = cells.map(function (c) {
      return '<div class="rc-stat' + c[3] + '"><small>' + esc(c[0]) + '</small><b>' + esc(c[1]) + '</b><span>' + esc(c[2]) + '</span></div>';
    }).join('');
  }

  function renderCats() {
    var counts = { all: 0 };
    recs.forEach(function (r) { if (!isDone(r) || state.showDone) { counts.all++; counts[r.cat] = (counts[r.cat] || 0) + 1; } });
    var html = '<button type="button" class="rc-pill' + (state.cat === 'all' ? ' active' : '') + '" data-cat="all">All<i>' + counts.all + '</i></button>';
    Object.keys(CATS).forEach(function (k) {
      if (!counts[k]) return;
      html += '<button type="button" class="rc-pill' + (state.cat === k ? ' active' : '') + '" data-cat="' + k + '">' + CATS[k] + '<i>' + counts[k] + '</i></button>';
    });
    $('cats').innerHTML = html;
  }

  function renderList() {
    var list = recs.filter(function (r) {
      if (state.cat !== 'all' && r.cat !== state.cat) return false;
      return state.showDone || !isDone(r);
    });
    $('empty').style.display = list.length ? 'none' : 'block';
    $('list').innerHTML = list.map(function (r) {
      var dn = isDone(r);
      return '<article class="rc-card rc-r p' + r.pri + (dn ? ' done' : '') + '">' +
        '<div class="rc-r-top"><div class="rc-tags">' +
          '<span class="rc-tag pri' + r.pri + '">' + PRI[r.pri] + '</span>' +
          '<span class="rc-tag">' + esc(CATS[r.cat]) + '</span>' +
          (r.crop ? '<span class="rc-tag crop">' + esc(r.crop) + '</span>' : '') +
        '</div><span class="rc-due">' + esc(r.due) + '</span></div>' +
        '<h3>' + esc(r.title) + '</h3>' +
        '<p class="rc-why">' + esc(r.why) + '</p>' +
        '<ul class="rc-steps">' + r.steps.map(function (s) { return '<li>' + esc(s) + '</li>'; }).join('') + '</ul>' +
        '<div class="rc-actions">' +
          '<button type="button" class="rc-btn primary" data-done="' + esc(r.id) + '">' + (dn ? 'Undo' : 'Mark as done') + '</button>' +
          '<a class="rc-btn" href="' + esc(r.link.href) + '">' + esc(r.link.label) + ' &rarr;</a>' +
        '</div></article>';
    }).join('');
  }

  function render() { renderStats(); renderCats(); renderList(); }

  $('cats').addEventListener('click', function (e) {
    var b = e.target.closest('.rc-pill'); if (!b) return;
    state.cat = b.getAttribute('data-cat'); render();
  });
  $('showDone').addEventListener('change', function () { state.showDone = this.checked; render(); });
  $('list').addEventListener('click', function (e) {
    var b = e.target.closest('[data-done]'); if (!b) return;
    var key = b.getAttribute('data-done') + '|' + TODAY;
    var on = !done[key];
    if (on) done[key] = true; else delete done[key];
    saveDone(key, on); render();
  });

  function start() {
    renderBanner(); renderPlan();
    recs = build();
    if (state.cat !== 'all' && !recs.some(function (r) { return r.cat === state.cat; })) state.cat = 'all';
    render();
  }

  // Crop and market recommendations show straight away, weather ones join when the forecast arrives
  recs = build(); render();

  var url = 'https://api.open-meteo.com/v1/forecast?latitude=' + farm.lat + '&longitude=' + farm.lon +
    '&current=temperature_2m,weather_code,wind_speed_10m,relative_humidity_2m' +
    '&daily=weather_code,temperature_2m_max,precipitation_probability_max,wind_speed_10m_max&timezone=auto&forecast_days=7';
  fetch(url)
    .then(function (r) { if (!r.ok) throw new Error('bad response'); return r.json(); })
    .then(function (d) { wx = d; start(); })
    .catch(function () { $('notice').style.display = 'block'; wx = null; start(); });
}