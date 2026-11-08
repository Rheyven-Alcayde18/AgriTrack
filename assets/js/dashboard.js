// assets/js/dashboard.js
// Dashboard: fills the weather panel using the place chosen on the Weather forecast page.
// Also remembers ticked to-do items. Loaded by user/dashboard.php
// (needs the ids dashPlace, dashTemp, dashCondition, dashRain, dashHumidity, dashWind).
(function () {
  var KEY = 'agritrack.location';
  var DEFAULT = { name: 'Bay, Laguna, Philippines', lat: 14.1833, lon: 121.2833 };
  var $ = function (id) { return document.getElementById(id); };

  function getLocation() {
    try {
      var v = JSON.parse(localStorage.getItem(KEY));
      if (v && isFinite(v.lat) && isFinite(v.lon) && v.name) return v;
    } catch (e) {}
    return DEFAULT;
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

  var loc = getLocation();
  $('dashPlace').textContent = loc.name;

  var url = 'https://api.open-meteo.com/v1/forecast'
    + '?latitude=' + loc.lat + '&longitude=' + loc.lon
    + '&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m'
    + '&daily=precipitation_probability_max&timezone=auto&forecast_days=1';

  fetch(url)
    .then(function (r) { if (!r.ok) throw new Error('bad response'); return r.json(); })
    .then(function (d) {
      var c = d.current;
      var rain = d.daily.precipitation_probability_max[0];
      $('dashTemp').textContent = Math.round(c.temperature_2m) + '\u00B0C';
      $('dashCondition').textContent = describe(c.weather_code);
      $('dashRain').textContent = (rain == null ? 0 : rain) + '%';
      $('dashHumidity').textContent = c.relative_humidity_2m + '%';
      $('dashWind').textContent = Math.round(c.wind_speed_10m) + ' km/h';
    })
    .catch(function () {
      $('dashCondition').textContent = 'Weather unavailable';
    });
})();

// To do list: remember which tasks are ticked on this device
(function () {
  var boxes = document.querySelectorAll('.db-tasks input[type="checkbox"]');
  var stamp = new Date().toDateString(); // ticks reset each day
  boxes.forEach(function (box) {
    var key = 'agritrack.task.' + box.getAttribute('data-task');
    try {
      var v = JSON.parse(localStorage.getItem(key));
      box.checked = !!(v && v.day === stamp);
    } catch (e) {}
    box.addEventListener('change', function () {
      try {
        if (box.checked) localStorage.setItem(key, JSON.stringify({ day: stamp }));
        else localStorage.removeItem(key);
      } catch (e) {}
    });
  });
})();