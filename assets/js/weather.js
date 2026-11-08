// assets/js/weather.js
// Used by weather-forecast.php. The Dashboard has its own inline script.

const KEY = 'agritrack.location';
const DEFAULT = { name: 'Bay, Laguna, Philippines', lat: 14.1833, lon: 121.2833 };

export function getLocation() {
  try {
    const v = JSON.parse(localStorage.getItem(KEY));
    if (v && Number.isFinite(v.lat) && Number.isFinite(v.lon) && v.name) return v;
  } catch (e) { /* storage blocked or corrupt: use default */ }
  return { ...DEFAULT };
}

export function saveLocation(loc) {
  try {
    localStorage.setItem(KEY, JSON.stringify({ name: loc.name, lat: loc.lat, lon: loc.lon }));
  } catch (e) { /* ignore */ }
}

export async function geocode(query) {
  const url = 'https://geocoding-api.open-meteo.com/v1/search?count=1&language=en&format=json&name=' + encodeURIComponent(query);
  const res = await fetch(url);
  if (!res.ok) throw new Error('Search failed');
  const data = await res.json();
  const r = data.results && data.results[0];
  if (!r) return null;
  return { name: [r.name, r.admin1, r.country].filter(Boolean).join(', '), lat: r.latitude, lon: r.longitude };
}

export async function fetchForecast(loc) {
  const p = new URLSearchParams({
    latitude: loc.lat,
    longitude: loc.lon,
    current: 'temperature_2m,apparent_temperature,relative_humidity_2m,precipitation,weather_code,wind_speed_10m',
    daily: 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,precipitation_sum,sunrise,sunset',
    timezone: 'auto',
    forecast_days: 7,
  });
  const res = await fetch('https://api.open-meteo.com/v1/forecast?' + p);
  if (!res.ok) throw new Error('Forecast failed');
  return res.json();
}

/* ---------- Icons (our own static SVG, no emoji) ---------- */
const CLOUD = 'M17.5 19a4.5 4.5 0 1 0-1-8.9A6 6 0 0 0 4.7 12 3.5 3.5 0 0 0 6.5 19Z';
const SMALL_CLOUD = '<path transform="translate(2.4 -1) scale(.8)" d="' + CLOUD + '"/>';
const ICONS = {
  sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6 6 6M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/>',
  partly: '<circle cx="17.5" cy="6.5" r="2.7"/><path d="M17.5 1.6v.9M22.4 6.5h-.9M21 3l-.6.6M14 3l.6.6"/><path transform="translate(0 3) scale(.88)" d="' + CLOUD + '"/>',
  cloud: '<path d="' + CLOUD + '"/>',
  fog: '<path d="M3 7h18M5 11.5h14M3 16h18M7 20.5h10"/>',
  rain: SMALL_CLOUD + '<path d="M8 17v3M12 17.5v3.5M16 17v3"/>',
  snow: SMALL_CLOUD + '<path d="M8 18h.01M12 20h.01M16 18h.01M10 21h.01M14 21h.01" stroke-width="2.6"/>',
  storm: SMALL_CLOUD + '<path d="M12.5 14.5 10 18.5h3L11.5 22"/>',
};
const DROP = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5C8 8 5.5 11.2 5.5 14.5a6.5 6.5 0 0 0 13 0C18.5 11.2 16 8 12 2.5Z"/></svg>';

function icon(key, size) {
  const s = size ? ' width="' + size + '" height="' + size + '"' : '';
  return '<svg class="wx-ic ' + key + '"' + s + ' viewBox="0 0 24 24" aria-hidden="true">' + ICONS[key] + '</svg>';
}

export function describe(code) {
  if (code === 0) return ['Clear sky', 'sun'];
  if (code <= 2) return ['Partly cloudy', 'partly'];
  if (code === 3) return ['Overcast', 'cloud'];
  if (code <= 48) return ['Foggy', 'fog'];
  if (code <= 57) return ['Drizzle', 'rain'];
  if (code <= 67) return ['Rain', 'rain'];
  if (code <= 77) return ['Snow', 'snow'];
  if (code <= 82) return ['Rain showers', 'rain'];
  if (code <= 86) return ['Snow showers', 'snow'];
  return ['Thunderstorm', 'storm'];
}

/* ---------- Weather page ---------- */
const $ = (id) => document.getElementById(id);
const time = (iso) => new Date(iso).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

function advice(d, c) {
  const rain = d.precipitation_probability_max[0] ?? 0;
  const max = d.temperature_2m_max[0];
  if (c.weather_code >= 95) {
    return { level: 'alert', title: 'Take precautions', text: 'Thunderstorms expected. Stay out of open fields and secure equipment.' };
  }
  if (rain >= 70) {
    return { level: 'warn', title: 'Plan around the rain', text: 'High chance of rain. Hold off on spraying and fertilizing, and check field drainage.' };
  }
  if (rain >= 40) {
    return { level: 'warn', title: 'Rain is possible', text: 'Do outdoor work early and keep covers ready for harvested produce.' };
  }
  if (max >= 34) {
    return { level: 'warn', title: 'Heat caution', text: 'Very hot today. Water early morning or late afternoon and give workers shade breaks.' };
  }
  return { level: 'good', title: 'Good day for field work', text: 'Good conditions for field work, spraying and harvesting.' };
}

function render(loc, data) {
  const c = data.current;
  const d = data.daily;
  const [label, key] = describe(c.weather_code);
  const now = new Date(c.time);

  $('placeName').textContent = loc.name;
  $('dateLine').textContent = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
  $('wIcon').innerHTML = icon(key);
  $('temp').textContent = Math.round(c.temperature_2m);
  $('condition').textContent = label;
  $('hiLo').textContent = 'High ' + Math.round(d.temperature_2m_max[0]) + '°  ·  Low ' + Math.round(d.temperature_2m_min[0]) + '°';

  $('mFeels').textContent = Math.round(c.apparent_temperature) + '°C';
  $('mRainChance').textContent = (d.precipitation_probability_max[0] ?? 0) + '%';
  $('mRainToday').textContent = (d.precipitation_sum[0] ?? 0) + ' mm';
  $('mHumidity').textContent = c.relative_humidity_2m + '%';
  $('mWind').textContent = Math.round(c.wind_speed_10m) + ' km/h';
  $('mDaylight').textContent = time(d.sunrise[0]);
  $('mDaylightSub').textContent = 'Sunrise · sunset ' + time(d.sunset[0]);

  const a = advice(d, c);
  $('advice').dataset.level = a.level;
  $('tipTitle').textContent = a.title;
  $('tip').textContent = a.text;
  $('updated').textContent = 'Last updated ' + time(c.time) + ', local time at this place.';

  // Shared scale so the range bars are comparable across the week
  const lows = d.temperature_2m_min;
  const highs = d.temperature_2m_max;
  const lo = Math.min(...lows) - 1;
  const hi = Math.max(...highs) + 1;
  const span = hi - lo || 1;

  const box = $('days');
  box.textContent = '';
  d.time.forEach((iso, i) => {
    const [dl, dk] = describe(d.weather_code[i]);
    const date = new Date(iso + 'T00:00:00');
    const rain = d.precipitation_probability_max[i] ?? 0;
    const left = ((lows[i] - lo) / span) * 100;
    const width = ((highs[i] - lows[i]) / span) * 100;

    const row = document.createElement('div');
    row.className = 'wx-row' + (i === 0 ? ' today' : '');
    // Only our own numbers and static icon markup go through innerHTML
    row.innerHTML =
      '<div class="wx-day"><b></b><small></small></div>' +
      icon(dk) +
      '<div class="wx-row-cond"></div>' +
      '<div class="wx-rainpct' + (rain < 10 ? ' dry' : '') + '">' + DROP + '<span>' + rain + '%</span></div>' +
      '<div class="wx-lo">' + Math.round(lows[i]) + '°</div>' +
      '<div class="wx-range"><i style="left:' + left.toFixed(1) + '%;width:' + Math.max(width, 4).toFixed(1) + '%"></i></div>' +
      '<div class="wx-hi">' + Math.round(highs[i]) + '°</div>';
    row.querySelector('.wx-day b').textContent = i === 0 ? 'Today' : date.toLocaleDateString('en-US', { weekday: 'long' });
    row.querySelector('.wx-day small').textContent = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    row.querySelector('.wx-row-cond').textContent = dl;
    box.appendChild(row);
  });
}

function initWeatherPage() {
  const root = $('wxRoot');
  const statusEl = $('status');
  let loc = getLocation();

  async function load() {
    root.classList.add('is-loading');
    statusEl.textContent = 'Loading forecast...';
    try {
      render(loc, await fetchForecast(loc));
      statusEl.textContent = '';
    } catch (e) {
      statusEl.textContent = 'Could not load the forecast. Check your connection and try again.';
    }
    root.classList.remove('is-loading');
  }

  $('searchForm').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const q = $('place').value.trim();
    if (!q) return;
    statusEl.textContent = 'Searching...';
    try {
      const found = await geocode(q);
      if (!found) {
        statusEl.textContent = 'No place found for "' + q + '". Your current location is unchanged.';
        return;
      }
      loc = found;
      saveLocation(loc); // remembered for next visit and for the Dashboard
      $('place').value = '';
      await load();
    } catch (e) {
      statusEl.textContent = 'Search failed. Please try again.';
    }
  });

  $('resetLoc').addEventListener('click', () => {
    loc = { ...DEFAULT };
    saveLocation(loc);
    load();
  });

  load();
}

if ($('searchForm')) initWeatherPage();