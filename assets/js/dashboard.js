// Dashboard: reads crops + harvests from Store, weather from Open-Meteo.

async function loadFarmData() {
  const [crops, harvests] = await Promise.all([Store.all('crops'), Store.all('harvests')]);
  const sum = (a, k) => a.reduce((s, x) => s + (+x[k] || 0), 0);
  const upcoming = crops.map((c) => ({ c, d: Crops.harvestDate(c) })).sort((a, b) => a.d - b.d);
  const now = new Date();
  const harvestedPerMonth = [0, 1, 2, 3, 4, 5].map((i) => {
    const m = new Date(now.getFullYear(), now.getMonth() - (5 - i), 1);
    return harvests.filter((h) => { const d = new Date(h.date); return d.getFullYear() === m.getFullYear() && d.getMonth() === m.getMonth(); })
      .reduce((s, h) => s + (+h.tons || 0), 0);
  });
  return {
    crops, upcoming, harvestedPerMonth,
    activeCrops: crops.length,
    totalArea: sum(crops, 'area'),
    fields: new Set(crops.map((c) => c.field.trim().toLowerCase())).size,
    expectedYield: sum(crops, 'yield'),
    nextHarvest: upcoming[0] ? Crops.fmt(upcoming[0].d, true) : null,
  };
}

function countUp(el, to, decimals = 0, ms = 900) {
  const t0 = performance.now();
  const tick = (now) => {
    const p = Math.min((now - t0) / ms, 1);
    el.firstChild.textContent = (to * (1 - Math.pow(1 - p, 3))).toFixed(decimals);
    if (p < 1) requestAnimationFrame(tick);
  };
  requestAnimationFrame(tick);
}

function renderStats(d) {
  countUp($('#activeCrops'), d.activeCrops);
  countUp($('#totalArea'), d.totalArea, 1);
  countUp($('#expectedYield'), d.expectedYield, 1);
  const ready = d.crops.filter((c) => Crops.daysLeft(c) <= 0).length;
  $('#fields').textContent = `Across ${d.fields} field${d.fields === 1 ? '' : 's'}`;
  $('#cropsNote').textContent = !d.activeCrops ? 'Nothing planted yet' : ready ? `${ready} ready to harvest` : 'All on track';
  $('#nextHarvest').textContent = d.nextHarvest ?? 'None';
  $('#nextNote').textContent = d.nextHarvest ? 'Earliest of your crops' : 'Add a crop to get started';
  document.querySelectorAll('.stat strong').forEach((e) => e.classList.remove('skeleton'));
}

function renderLists(d) {
  if (d.crops.length) {
    $('#cropList').innerHTML = '<div class="list">' + d.crops.slice(0, 3).map((c) =>
      `<div class="item" style="display:block"><div class="row"><b>${esc(c.name)}</b><span class="mini">${esc(c.field)}</span></div>${progressHTML(c)}</div>`).join('') + '</div>';
    growBars($('#cropList'));
  }
  if (d.upcoming.length) {
    $('#upNext').innerHTML = '<div class="list">' + d.upcoming.slice(0, 3).map(({ c, d: date }) =>
      `<div class="item"><b>${esc(c.name)}</b><span class="mini">${Crops.fmt(date, true)}</span></div>`).join('') + '</div>';
  }
  const todos = d.crops.filter((c) => Crops.daysLeft(c) <= 7);
  if (todos.length) {
    $('#todo').innerHTML = '<div class="list">' + todos.map((c) => {
      const n = Crops.daysLeft(c);
      return `<div class="item"><span>${n <= 0 ? 'Harvest' : 'Prepare to harvest'} ${esc(c.name)}</span><span class="mini">${n <= 0 ? 'Now' : 'in ' + n + ' days'}</span></div>`;
    }).join('') + '</div>';
  }
}

function renderChart(values) {
  const chart = $('#chart'), now = new Date(), max = Math.max(...values, 1);
  chart.innerHTML = '';
  values.forEach((v, i) => {
    const m = new Date(now.getFullYear(), now.getMonth() - (5 - i), 1);
    const bar = document.createElement('div');
    bar.className = 'bar' + (v > 0 ? ' has' : '');
    bar.innerHTML = `<b>${+v.toFixed(1)}</b><div class="fill"></div><span>${m.toLocaleString('en', { month: 'short' })}</span>`;
    chart.appendChild(bar);
    setTimeout(() => (bar.querySelector('.fill').style.height = v > 0 ? `${(v / max) * 130}px` : '4px'), 300 + i * 90);
  });
  $('#chartNote').textContent = values.some((v) => v > 0) ? 'Tons harvested in the last 6 months.' : 'No harvests recorded in the last 6 months yet.';
}

async function renderWeather() {
  const clear = () => document.querySelectorAll('.weather .skeleton').forEach((e) => e.classList.remove('skeleton'));
  try {
    const r = await getWeather(), c = r.current;
    const [label, icon] = WX[c.weather_code] ?? ['Partly cloudy', '⛅'];
    $('#tempVal').textContent = Math.round(c.temperature_2m);
    $('#wIcon').textContent = icon;
    $('#wCond').textContent = label;
    $('#wRain').textContent = (r.daily.precipitation_probability_max[0] ?? 0) + '%';
    $('#wHum').textContent = c.relative_humidity_2m + '%';
    $('#wWind').textContent = Math.round(c.wind_speed_10m) + ' km/h';
  } catch { $('#wCond').textContent = 'Weather unavailable'; }
  clear();
}

document.addEventListener('DOMContentLoaded', async () => {
  renderWeather();
  const data = await loadFarmData();
  renderStats(data); renderLists(data); renderChart(data.harvestedPerMonth);
});
