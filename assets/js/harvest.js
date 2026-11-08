// assets/js/harvest.js
// Harvest management: remembers which preparation checklist items are ticked (per browser).
// Loaded by user/harvest-management.php.
(function () {
  // Remember ticked tasks on this device (light convenience only)
  var boxes = document.querySelectorAll('.hm-group input[type="checkbox"]');
  boxes.forEach(function (box) {
    var key = box.getAttribute('data-key');
    try { box.checked = localStorage.getItem(key) === '1'; } catch (e) {}
    box.addEventListener('change', function () {
      try { localStorage.setItem(key, box.checked ? '1' : '0'); } catch (e) {}
    });
  });
})();