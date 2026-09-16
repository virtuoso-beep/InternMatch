/* InternMatch — UI interactions (vanilla JS, Bootstrap 5 compatible) */
(function () {
  'use strict';

  /* ---------- Theme (dark mode) ---------- */
  const THEME_KEY = 'internmatch.theme';
  function applyTheme(t) {
    document.body.classList.toggle('dark', t === 'dark');
    document.querySelectorAll('[data-theme-icon]').forEach(function (i) {
      i.className = (t === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars');
    });
  }
  window.IMToggleTheme = function () {
    const next = document.body.classList.contains('dark') ? 'light' : 'dark';
    localStorage.setItem(THEME_KEY, next);
    applyTheme(next);
  };

  /* ---------- Sidebar ---------- */
  const SB_KEY = 'internmatch.sidebar';
  window.IMToggleSidebar = function () {
    if (window.innerWidth < 992) {
      document.body.classList.toggle('sidebar-open');
    } else {
      document.body.classList.toggle('sidebar-collapsed');
      localStorage.setItem(SB_KEY, document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
    }
  };

  /* ---------- Toasts ---------- */
  window.IMToast = function (message, type) {
    type = type || 'success';
    let host = document.getElementById('im-toasts');
    if (!host) {
      host = document.createElement('div');
      host.id = 'im-toasts';
      host.style.cssText = 'position:fixed;right:20px;bottom:20px;z-index:1090;display:flex;flex-direction:column;gap:10px';
      document.body.appendChild(host);
    }
    const icons = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
    const colors = { success: 'var(--success)', danger: 'var(--danger)', warning: 'var(--warning)', info: 'var(--info)' };
    const el = document.createElement('div');
    el.className = 'toast-x fade-up';
    el.setAttribute('role', 'status');
    el.innerHTML = '<i class="bi ' + (icons[type] || icons.info) + '" style="color:' + (colors[type] || colors.info) + '"></i>' +
      '<div class="flex-grow-1 fs-13">' + message + '</div>' +
      '<button class="btn-close btn-close-sm" aria-label="Dismiss"></button>';
    el.querySelector('button').onclick = function () { el.remove(); };
    host.appendChild(el);
    setTimeout(function () { el.style.opacity = '0'; setTimeout(function () { el.remove(); }, 300); }, 4200);
  };

  /* ---------- Scroll reveal ---------- */
  function initReveal() {
    const items = document.querySelectorAll('.reveal');
    if (!items.length) return;
    if (!('IntersectionObserver' in window)) { items.forEach(function (i) { i.classList.add('in'); }); return; }
    const io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12 });
    items.forEach(function (i) { io.observe(i); });
  }

  /* ---------- Animated counters ---------- */
  function initCounters() {
    const els = document.querySelectorAll('[data-count]');
    if (!els.length) return;
    const run = function (el) {
      const target = parseFloat(el.dataset.count);
      const suffix = el.dataset.suffix || '';
      const dur = 1400; const start = performance.now();
      const tick = function (now) {
        const p = Math.min((now - start) / dur, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        const v = target * eased;
        el.textContent = (target % 1 ? v.toFixed(1) : Math.round(v).toLocaleString()) + suffix;
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    if (!('IntersectionObserver' in window)) { els.forEach(run); return; }
    const io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } });
    }, { threshold: 0.4 });
    els.forEach(function (e) { io.observe(e); });
  }

  /* ---------- Public nav shrink ---------- */
  function initNavScroll() {
    const nav = document.querySelector('.pub-nav:not(.inner)');
    if (!nav) return;
    const onScroll = function () { nav.classList.toggle('solid', window.scrollY > 30); };
    onScroll(); window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------- Accordion ---------- */
  function initAccordion() {
    document.querySelectorAll('.accordion-x .ac-item > button').forEach(function (btn) {
      btn.setAttribute('aria-expanded', 'false');
      btn.addEventListener('click', function () {
        const item = btn.parentElement;
        const open = item.classList.contains('open');
        item.parentElement.querySelectorAll('.ac-item').forEach(function (i) {
          i.classList.remove('open'); i.querySelector('button').setAttribute('aria-expanded', 'false');
        });
        if (!open) { item.classList.add('open'); btn.setAttribute('aria-expanded', 'true'); }
      });
    });
  }

  /* ---------- Table: search + sort + select all ---------- */
  function initTables() {
    document.querySelectorAll('[data-table-search]').forEach(function (input) {
      const table = document.querySelector(input.dataset.tableSearch);
      if (!table) return;
      input.addEventListener('input', function () {
        const q = input.value.toLowerCase().trim();
        table.querySelectorAll('tbody tr').forEach(function (tr) {
          tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    });
    document.querySelectorAll('th[data-sort]').forEach(function (th) {
      th.style.cursor = 'pointer';
      th.addEventListener('click', function () {
        const table = th.closest('table');
        const idx = Array.prototype.indexOf.call(th.parentElement.children, th);
        const asc = th.dataset.dir !== 'asc';
        th.dataset.dir = asc ? 'asc' : 'desc';
        const body = table.tBodies[0];
        Array.from(body.rows)
          .sort(function (a, b) {
            const x = a.cells[idx].innerText.trim(), y = b.cells[idx].innerText.trim();
            const nx = parseFloat(x.replace(/[^0-9.-]/g, '')), ny = parseFloat(y.replace(/[^0-9.-]/g, ''));
            if (!isNaN(nx) && !isNaN(ny)) return asc ? nx - ny : ny - nx;
            return asc ? x.localeCompare(y) : y.localeCompare(x);
          })
          .forEach(function (r) { body.appendChild(r); });
      });
    });
    document.querySelectorAll('[data-check-all]').forEach(function (master) {
      master.addEventListener('change', function () {
        const table = master.closest('table');
        table.querySelectorAll('tbody input[type=checkbox]').forEach(function (c) { c.checked = master.checked; });
        const bar = document.querySelector(master.dataset.checkAll);
        if (bar) bar.classList.toggle('d-none', !master.checked);
      });
    });
  }

  /* ---------- Dropzone ---------- */
  function initDropzones() {
    document.querySelectorAll('.dropzone').forEach(function (dz) {
      const input = dz.querySelector('input[type=file]');
      dz.addEventListener('click', function () { if (input) input.click(); });
      ['dragenter', 'dragover'].forEach(function (ev) {
        dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.add('dragover'); });
      });
      ['dragleave', 'drop'].forEach(function (ev) {
        dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.remove('dragover'); });
      });
      dz.addEventListener('drop', function (e) {
        const f = e.dataTransfer.files[0];
        if (f) IMToast('Ready to upload: <strong>' + f.name + '</strong>', 'info');
      });
      if (input) input.addEventListener('change', function () {
        if (input.files[0]) IMToast('Selected: <strong>' + input.files[0].name + '</strong>', 'info');
      });
    });
  }

  /* ---------- Filter chips ---------- */
  function initChips() {
    document.querySelectorAll('[data-chip-group]').forEach(function (group) {
      group.querySelectorAll('.chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
          group.querySelectorAll('.chip').forEach(function (c) { c.classList.remove('active'); });
          chip.classList.add('active');
        });
      });
    });
  }

  /* ---------- Tabs ---------- */
  function initTabs() {
    document.querySelectorAll('[data-tabs]').forEach(function (nav) {
      nav.querySelectorAll('a[data-tab-target]').forEach(function (a) {
        a.addEventListener('click', function (e) {
          e.preventDefault();
          nav.querySelectorAll('a').forEach(function (x) { x.classList.remove('active'); });
          a.classList.add('active');
          document.querySelectorAll(nav.dataset.tabs + ' [data-tab-panel]').forEach(function (p) {
            p.classList.toggle('d-none', p.dataset.tabPanel !== a.dataset.tabTarget);
          });
        });
      });
    });
  }

  /* ---------- Autosave indicator ---------- */
  function initAutosave() {
    document.querySelectorAll('[data-autosave]').forEach(function (form) {
      const badge = form.querySelector('.autosave span');
      let t;
      form.addEventListener('input', function () {
        if (!badge) return;
        badge.textContent = 'Saving draft…';
        clearTimeout(t);
        t = setTimeout(function () { badge.textContent = 'Draft saved just now'; }, 900);
      });
    });
  }

  /* ---------- Keyboard shortcut: focus search ---------- */
  document.addEventListener('keydown', function (e) {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
      const s = document.querySelector('.search-x input');
      if (s) { e.preventDefault(); s.focus(); }
    }
    if (e.key === 'Escape') document.body.classList.remove('sidebar-open');
  });

  document.addEventListener('DOMContentLoaded', function () {
    applyTheme(localStorage.getItem(THEME_KEY) || 'light');
    if (localStorage.getItem(SB_KEY) === '1' && window.innerWidth >= 992) document.body.classList.add('sidebar-collapsed');
    initReveal(); initCounters(); initNavScroll(); initAccordion();
    initTables(); initDropzones(); initChips(); initTabs(); initAutosave();
    const bd = document.querySelector('.sidebar-backdrop');
    if (bd) bd.addEventListener('click', function () { document.body.classList.remove('sidebar-open'); });
    document.querySelectorAll('form[data-demo]').forEach(function (f) {
      f.addEventListener('submit', function (e) { e.preventDefault(); IMToast('Form submitted — connect this to your Laravel controller.', 'success'); });
    });
  });
})();
