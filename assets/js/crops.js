// assets/js/crops.js
// Crop monitoring: filter the crop cards by status (All, On track, Harvest soon, Needs attention).
// Loaded by user/crop-monitoring.php.
(function () {
  var buttons = document.querySelectorAll('.cm-filter');
  var cards = document.querySelectorAll('.cm-card');
  var empty = document.getElementById('empty');

  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var f = btn.getAttribute('data-filter');
      var shown = 0;
      buttons.forEach(function (b) { b.classList.toggle('active', b === btn); });
      cards.forEach(function (card) {
        var match = f === 'all' || card.getAttribute('data-status') === f;
        card.hidden = !match;
        if (match) shown++;
      });
      empty.style.display = shown ? 'none' : 'block';
    });
  });
})();