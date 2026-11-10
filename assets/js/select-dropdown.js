// assets/js/select-dropdown.js
// Replaces the browser's plain dropdown popup with a styled list, for ONE <select>.
// The real <select> stays in the page, so required-validation, .value and form code keep working.
// Touch-only phones and tablets keep the native picker (it is better there).
//
// Use:  import { enhanceSelect } from "./assets/js/select-dropdown.js";
//       enhanceSelect(document.getElementById("municipality"));   // after the options are added

const CSS = `
.sd-panel {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  z-index: 50;
  max-height: 264px;
  overflow-y: auto;
  margin: 0;
  padding: 6px;
  list-style: none;
  background: #ffffff;
  border: 1px solid #dbe4d2;
  border-radius: 14px;
  box-shadow: 0 18px 40px rgba(18, 61, 42, 0.18), 0 2px 6px rgba(18, 61, 42, 0.06);
  scrollbar-width: thin;
  scrollbar-color: #b9cdb9 transparent;
  animation: sd-in 0.16s ease both;
}
.sd-panel[hidden] { display: none; }
@keyframes sd-in {
  from { opacity: 0; transform: translateY(-6px); }
  to   { opacity: 1; transform: translateY(0); }
}
.sd-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 15px;
  line-height: 1.3;
  color: #1c2a22;
  cursor: pointer;
}
.sd-item.is-active { background: #eef7e2; color: #123d2a; }
.sd-item[aria-selected="true"] { font-weight: 700; color: #1f6b36; }
.sd-item[aria-selected="true"]::after {
  content: "";
  flex-shrink: 0;
  width: 6px;
  height: 11px;
  margin-right: 4px;
  border: solid #287b4c;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg) translateY(-2px);
}
@media (prefers-reduced-motion: reduce) { .sd-panel { animation: none; } }
`;

export function enhanceSelect(select) {
  if (!select || select.dataset.sdReady) return;
  // keep the native picker only on touch-only devices (phones, tablets), not on laptops with a touchscreen
  if (window.matchMedia && window.matchMedia("(hover: none) and (pointer: coarse)").matches) return;
  select.dataset.sdReady = "1";

  if (!document.getElementById("sd-css")) {
    const style = document.createElement("style");
    style.id = "sd-css";
    style.textContent = CSS;
    document.head.appendChild(style);
  }

  const host = select.closest(".input-container") || select.parentElement;
  if (getComputedStyle(host).position === "static") host.style.position = "relative";

  const panel = document.createElement("ul");
  panel.className = "sd-panel";
  panel.id = (select.id || "select") + "-list";
  panel.setAttribute("role", "listbox");
  panel.hidden = true;
  host.appendChild(panel);

  select.setAttribute("aria-haspopup", "listbox");
  select.setAttribute("aria-controls", panel.id);
  select.setAttribute("aria-expanded", "false");

  let items = [];
  let active = -1;
  let typed = "";
  let typedTimer = null;

  function build() {
    panel.innerHTML = "";
    items = [];
    Array.from(select.options).forEach(function (opt) {
      if (opt.value === "") return; // the "Choose..." placeholder is not a choice
      const li = document.createElement("li");
      li.className = "sd-item";
      li.setAttribute("role", "option");
      li.dataset.value = opt.value;
      li.textContent = opt.textContent;
      li.addEventListener("mousedown", function (e) { e.preventDefault(); choose(opt.value); });
      li.addEventListener("mousemove", function () { setActive(items.indexOf(li)); });
      panel.appendChild(li);
      items.push(li);
    });
    markSelected();
  }

  function markSelected() {
    items.forEach(function (li) {
      li.setAttribute("aria-selected", li.dataset.value === select.value ? "true" : "false");
    });
  }

  function setActive(index) {
    if (!items.length) return;
    active = Math.max(0, Math.min(items.length - 1, index));
    items.forEach(function (li, i) { li.classList.toggle("is-active", i === active); });
    items[active].scrollIntoView({ block: "nearest" });
  }

  function open() {
    build(); // pick up options that were added after the page loaded
    panel.hidden = false;
    select.setAttribute("aria-expanded", "true");
    const selectedIndex = items.findIndex(function (li) { return li.dataset.value === select.value; });
    setActive(selectedIndex >= 0 ? selectedIndex : 0);
  }

  function close() {
    panel.hidden = true;
    select.setAttribute("aria-expanded", "false");
  }

  function choose(value) {
    select.value = value;
    select.dispatchEvent(new Event("input", { bubbles: true }));
    select.dispatchEvent(new Event("change", { bubbles: true }));
    close();
    select.focus();
  }

  // Stop the browser's own popup and open ours instead
  select.addEventListener("mousedown", function (e) {
    e.preventDefault();
    select.focus();
    if (panel.hidden) open(); else close();
  });

  select.addEventListener("keydown", function (e) {
    const isOpen = !panel.hidden;

    if (!isOpen) {
      if (e.key === "ArrowDown" || e.key === "ArrowUp" || e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        open();
      }
      return;
    }

    switch (e.key) {
      case "ArrowDown": e.preventDefault(); setActive(active + 1); break;
      case "ArrowUp":   e.preventDefault(); setActive(active - 1); break;
      case "Home":      e.preventDefault(); setActive(0); break;
      case "End":       e.preventDefault(); setActive(items.length - 1); break;
      case "Enter":
      case " ":
        e.preventDefault();
        if (active >= 0) choose(items[active].dataset.value);
        break;
      case "Escape":    e.preventDefault(); close(); break;
      case "Tab":       close(); break;
      default:
        // type a few letters to jump to a town
        if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
          typed += e.key.toLowerCase();
          clearTimeout(typedTimer);
          typedTimer = setTimeout(function () { typed = ""; }, 700);
          const hit = items.findIndex(function (li) { return li.textContent.toLowerCase().startsWith(typed); });
          if (hit >= 0) setActive(hit);
        }
    }
  });

  document.addEventListener("mousedown", function (e) {
    if (!panel.hidden && !host.contains(e.target)) close();
  });

  select.addEventListener("blur", function () {
    // short delay so a click on an item still registers
    setTimeout(function () { if (document.activeElement !== select) close(); }, 120);
  });

  select.addEventListener("change", markSelected);
}