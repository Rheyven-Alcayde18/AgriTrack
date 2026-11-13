// assets/js/sidebar.js
// Collapse / expand the sidebar and remember the choice on every page.
// The class is applied before the page paints by a tiny script in each header.php,
// so there is no flash when you move between pages.
(function () {
  var KEY = "agritrack.nav";
  var root = document.documentElement;
  var btn = document.getElementById("navToggle");
  if (!btn) return;

  function apply(collapsed) {
    root.classList.toggle("nav-collapsed", collapsed);
    btn.setAttribute("aria-expanded", collapsed ? "false" : "true");
    btn.setAttribute("aria-label", collapsed ? "Expand sidebar" : "Collapse sidebar");
    btn.title = collapsed ? "Expand sidebar" : "Collapse sidebar";
  }

  apply(root.classList.contains("nav-collapsed"));

  btn.addEventListener("click", function () {
    var collapsed = !root.classList.contains("nav-collapsed");
    apply(collapsed);
    try { localStorage.setItem(KEY, collapsed ? "collapsed" : "open"); } catch (e) {}
  });
})();