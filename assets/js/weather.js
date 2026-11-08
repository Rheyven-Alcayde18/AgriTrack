const DEFAULT_PLACE = { name: "Nagcarlan, Laguna", lat: 14.14, lon: 121.42 };
const $ = (id) => document.getElementById(id);

let activePlace = DEFAULT_PLACE;

const CODES = {
  0: ["Clear sky", "☀️"], 1: ["Mostly clear", "🌤️"], 2: ["Partly cloudy", "⛅"], 3: ["Overcast", "☁️"],
  45: ["Fog", "🌫️"], 48: ["Fog", "🌫️"],
  51: ["Light drizzle", "🌦️"], 53: ["Drizzle", "🌦️"], 55: ["Heavy drizzle", "🌧️"],
  61: ["Light rain", "🌧️"], 63: ["Rain", "🌧️"], 65: ["Heavy rain", "🌧️"],
  80: ["Light showers", "🌦️"], 81: ["Showers", "🌧️"], 82: ["Heavy showers", "⛈️"],
  95: ["Thunderstorm", "⛈️"], 96: ["Thunderstorm with hail", "⛈️"], 99: ["Thunderstorm with hail", "⛈️"],
};
const info = (code) => CODES[code] || ["Mixed conditions", "🌡️"];

function setStatus(text, isError = false) {
  const el = $("status");
  el.textContent = text;
  el.classList.toggle("error", isError);
}

function advice(rainChance, temp, wind) {
  if (rainChance >= 70) return "Heavy rain is likely. Hold off on spraying and fertilizing, and check that your drainage is clear.";
  if (rainChance >= 40) return "Rain is possible. Finish spraying early in the day, and plan indoor work for the afternoon.";
  if (temp >= 34) return "It is very hot. Water early in the morning or late afternoon and avoid heavy work at midday.";
  if (wind >= 30) return "It is windy. Avoid spraying, since the drift can waste product and harm nearby crops.";
  return "Conditions look good for field work, spraying, and fertilizing.";
}

async function loadWeather(place) {
  activePlace = place;
  setStatus("Loading forecast...");
  const params = new URLSearchParams({
    latitude: place.lat,
    longitude: place.lon,
    current: "temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m,weather_code",
    daily: "weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max",
    timezone: "auto",
    forecast_days: 7,
  });

  try {
    const res = await fetch("https://api.open-meteo.com/v1/forecast?" + params);
    if (!res.ok) throw new Error("HTTP " + res.status);
    const data = await res.json();
    render(place, data);
    setStatus("Updated " + new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }));
    try { localStorage.setItem("agritrack-place", JSON.stringify(place)); } catch (e) {}
  } catch (err) {
    setStatus("Could not load the forecast. Check your internet connection and try again.", true);
  }
}

function render(place, data) {
  const c = data.current, d = data.daily;
  const [label, emoji] = info(c.weather_code);
  const rainChance = d.precipitation_probability_max[0] ?? 0;

  $("placeName").textContent = place.name;
  $("emoji").textContent = emoji;
  $("temp").textContent = Math.round(c.temperature_2m);
  $("condition").textContent = label;
  $("rainChance").textContent = rainChance + "%";
  $("humidity").textContent = c.relative_humidity_2m + "%";
  $("wind").textContent = Math.round(c.wind_speed_10m) + " km/h";
  $("rainNow").textContent = c.precipitation + " mm";
  $("tip").textContent = advice(rainChance, c.temperature_2m, c.wind_speed_10m);

  $("days").innerHTML = d.time.map((day, i) => {
    const [, icon] = info(d.weather_code[i]);
    const name = i === 0 ? "Today" : new Date(day + "T00:00").toLocaleDateString("en-US", { weekday: "short" });
    return `<div class="day">
      <b>${name}</b>
      <span class="ico">${icon}</span>
      <p><strong>${Math.round(d.temperature_2m_max[i])}°</strong> <em>${Math.round(d.temperature_2m_min[i])}°</em></p>
      <small>${d.precipitation_probability_max[i] ?? 0}% rain</small>
    </div>`;
  }).join("");
}

async function findPlace(query) {
  const url = "https://geocoding-api.open-meteo.com/v1/search?count=1&language=en&format=json&name=" + encodeURIComponent(query);
  const res = await fetch(url);
  const data = await res.json();
  if (!data.results || !data.results.length) return null;
  const r = data.results[0];
  return { name: [r.name, r.admin1].filter(Boolean).join(", "), lat: r.latitude, lon: r.longitude };
}

$("searchForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const q = $("place").value.trim();
  if (!q) return;
  setStatus("Searching...");
  try {
    const place = await findPlace(q);
    if (!place) return setStatus("Place not found. Try a nearby town or check the spelling.", true);
    loadWeather(place);
  } catch (err) {
    setStatus("Search failed. Check your internet connection and try again.", true);
  }
});

// Start with the last searched place, then refresh every 10 minutes
try { activePlace = JSON.parse(localStorage.getItem("agritrack-place")) || DEFAULT_PLACE; } catch (e) {}
loadWeather(activePlace);
setInterval(() => loadWeather(activePlace), 10 * 60 * 1000);