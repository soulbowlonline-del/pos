/*
 * modern-ui.js - small helpers for the modern look of the Yii 2 port.
 *
 * Self-contained, no jQuery, no network. Everything it shows is created
 * here at runtime, so the server-rendered page (its text, ids, names and
 * behaviour) is exactly what it was. It never throws: each helper is wrapped.
 *
 *   - the theme toggle in the navbar (modern <-> classic, remembered in
 *     localStorage.posUiTheme; classic removes html.ui-modern, which is
 *     today's look exactly);
 *   - "Go to..." (Ctrl+K): a quick filter over the links already in the
 *     sidebar - only the ones this user can see, nothing added;
 *   - the sidebar link of the current page is marked (a class, no change
 *     to the menu itself);
 *   - a "back to top" button on long pages.
 */
(function () {
  'use strict';
  var doc = document, root = doc.documentElement, KEY = 'posUiTheme';

  function isModern() { return (' ' + root.className + ' ').indexOf(' ui-modern ') !== -1; }
  function store(v) { try { window.localStorage.setItem(KEY, v); } catch (e) {} }
  function safe(fn) { try { fn(); } catch (e) { if (window.console && console.warn) console.warn('modern-ui:', e); } }
  function el(tag, cls, attrs) {
    var e = doc.createElement(tag);
    if (cls) e.className = cls;
    for (var k in attrs || {}) if (Object.prototype.hasOwnProperty.call(attrs, k)) e.setAttribute(k, attrs[k]);
    return e;
  }
  function text(node) { return (node.textContent || '').replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, ''); }

  /* ---- theme toggle -------------------------------------------------- */
  var toggleLink, gotoItem, topBtn;
  function applyTheme(modern) {
    if (modern) root.classList.add('ui-modern'); else root.classList.remove('ui-modern');
    store(modern ? 'modern' : 'classic');
    syncToggle();
    if (!modern) closePalette();
    onScroll();
  }
  function syncToggle() {
    if (!toggleLink) return;
    var t = isModern() ? 'Switch to classic look' : 'Switch to modern look';
    toggleLink.setAttribute('title', t);
    toggleLink.setAttribute('aria-label', t);
    if (gotoItem) gotoItem.style.display = isModern() ? '' : 'none';
  }
  function navIcon(liClass, iconClass, title, onClick) {
    var li = el('li', liClass);
    var a = el('a', null, { href: '#', role: 'button', title: title, 'aria-label': title });
    a.appendChild(el('i', 'fa ' + iconClass, { 'aria-hidden': 'true' }));
    a.addEventListener('click', function (e) { e.preventDefault(); onClick(); });
    li.appendChild(a);
    return li;
  }
  function buildToggle() {
    var ul = doc.querySelector('.main-header .navbar-custom-menu ul.nav.navbar-nav') ||
             doc.querySelector('.main-header ul.nav.navbar-nav');
    if (!ul) return;
    var bell = null;
    for (var i = 0; i < ul.children.length; i++) {
      if (/\bnotifications-menu\b/.test(ul.children[i].className)) { bell = ul.children[i]; break; }
    }
    var before = bell || ul.firstChild;
    var toggle = navIcon('ui-theme-toggle', 'fa-adjust', 'Switch to classic look', function () { applyTheme(!isModern()); });
    toggleLink = toggle.firstChild;
    if (sidebarLinks().length) {
      gotoItem = navIcon('ui-goto', 'fa-search', 'Go to… (Ctrl+K)', function () { openPalette(); });
      ul.insertBefore(gotoItem, before);
    }
    ul.insertBefore(toggle, before);
    syncToggle();
  }

  /* ---- sidebar links, and the current page --------------------------- */
  var linksCache = null;
  function sidebarLinks() {
    if (linksCache) return linksCache;
    var out = [], seen = {};
    var as = doc.querySelectorAll('.main-sidebar .sidebar-menu a[href]');
    for (var i = 0; i < as.length; i++) {
      var a = as[i], href = a.getAttribute('href') || '';
      if (!href || href.charAt(0) === '#' || /^javascript:/i.test(href)) continue;
      var clone = a.cloneNode(true), junk = clone.querySelectorAll('.sidebar-counter, .label, .badge, .pull-right-container');
      for (var j = 0; j < junk.length; j++) junk[j].parentNode.removeChild(junk[j]);
      var label = text(clone);
      if (!label) continue;
      var group = '', tv = a.parentNode && a.parentNode.parentNode;
      if (tv && /\btreeview-menu\b/.test(tv.className) && tv.parentNode) {
        var head = tv.parentNode.querySelector('a');
        if (head) { var hc = head.cloneNode(true), hj = hc.querySelectorAll('.pull-right-container');
          for (var k = 0; k < hj.length; k++) hj[k].parentNode.removeChild(hj[k]); group = text(hc); }
      }
      var key = a.href + '|' + label;
      if (seen[key]) continue;
      seen[key] = 1;
      out.push({ a: a, href: a.href, label: label, group: group, hay: (group + ' ' + label).toLowerCase() });
    }
    linksCache = out;
    return out;
  }
  function markCurrent() {
    var here = window.location.pathname.replace(/\/+$/, '').toLowerCase();
    if (!here) return;
    var links = sidebarLinks();
    for (var i = 0; i < links.length; i++) {
      var a = links[i].a, p;
      try { p = (a.pathname || '').replace(/\/+$/, '').toLowerCase(); } catch (e) { continue; }
      if (a.host !== window.location.host || p !== here) continue;
      a.classList.add('ui-current');
      a.setAttribute('aria-current', 'page');
      var tv = a.parentNode && a.parentNode.parentNode;
      if (tv && /\btreeview-menu\b/.test(tv.className) && tv.parentNode) tv.parentNode.classList.add('ui-current-parent');
    }
  }

  /* ---- Go to... palette ---------------------------------------------- */
  var pal, palInput, palList, palItems = [], palSel = 0, lastFocus = null;
  function buildPalette() {
    pal = el('div', 'ui-palette', { role: 'dialog', 'aria-modal': 'true', 'aria-label': 'Go to page' });
    var box = el('div', 'ui-palette-box');
    palInput = el('input', 'ui-palette-input', { type: 'text', autocomplete: 'off', spellcheck: 'false',
      'aria-label': 'Type to find a menu entry', placeholder: 'Go to…  type part of a menu name' });
    palList = el('ul', 'ui-palette-list', { role: 'listbox' });
    var hint = el('div', 'ui-palette-hint');
    hint.textContent = '↑ ↓ to choose · Enter to open · Esc to close';
    box.appendChild(palInput); box.appendChild(palList); box.appendChild(hint);
    pal.appendChild(box);
    // Keep the page's own key handlers (barcode fields, grids) out of this.
    ['keydown', 'keypress', 'keyup', 'input', 'change'].forEach(function (t) {
      pal.addEventListener(t, function (e) { e.stopPropagation(); }, false);
    });
    pal.addEventListener('mousedown', function (e) { if (e.target === pal) closePalette(); });
    palInput.addEventListener('input', renderPalette);
    palInput.addEventListener('keydown', function (e) {
      var k = e.key;
      if (k === 'ArrowDown' || k === 'Down') { e.preventDefault(); select(palSel + 1); }
      else if (k === 'ArrowUp' || k === 'Up') { e.preventDefault(); select(palSel - 1); }
      else if (k === 'Enter') { e.preventDefault(); go(palSel); }
      else if (k === 'Escape' || k === 'Esc') { e.preventDefault(); closePalette(); }
    });
    doc.body.appendChild(pal);
  }
  function renderPalette() {
    var q = (palInput.value || '').toLowerCase().split(/\s+/).filter(Boolean);
    var links = sidebarLinks();
    palItems = links.filter(function (l) { return q.every(function (w) { return l.hay.indexOf(w) !== -1; }); }).slice(0, 60);
    palList.innerHTML = '';
    if (!palItems.length) {
      var none = el('li', 'ui-palette-empty'); none.textContent = 'No menu entry matches';
      palList.appendChild(none);
    }
    palItems.forEach(function (l, i) {
      var li = el('li', null, { role: 'option' });
      if (l.group) { var g = el('span', 'ui-palette-group'); g.textContent = l.group; li.appendChild(g); }
      li.appendChild(doc.createTextNode(l.label));
      li.addEventListener('mousemove', function () { if (palSel !== i) select(i); });
      li.addEventListener('click', function () { go(i); });
      palList.appendChild(li);
    });
    select(0);
  }
  function select(i) {
    if (!palItems.length) { palSel = 0; return; }
    palSel = (i + palItems.length) % palItems.length;
    var lis = palList.children;
    for (var j = 0; j < lis.length; j++) {
      var on = j === palSel;
      lis[j].className = on ? 'ui-sel' : '';
      lis[j].setAttribute('aria-selected', on ? 'true' : 'false');
      if (on && lis[j].scrollIntoView) lis[j].scrollIntoView({ block: 'nearest' });
    }
  }
  function go(i) {
    var l = palItems[i];
    if (!l) return;
    closePalette();
    window.location.href = l.href;
  }
  function openPalette() {
    if (!isModern() || !sidebarLinks().length) return;
    if (!pal) buildPalette();
    lastFocus = doc.activeElement;
    palInput.value = '';
    pal.classList.add('ui-open');
    renderPalette();
    palInput.focus();
  }
  function closePalette() {
    if (!pal || !/\bui-open\b/.test(pal.className)) return;
    pal.classList.remove('ui-open');
    if (lastFocus && lastFocus.focus) { try { lastFocus.focus(); } catch (e) {} }
  }
  function onKey(e) {
    if ((e.ctrlKey || e.metaKey) && !e.altKey && !e.shiftKey && (e.key === 'k' || e.key === 'K')) {
      if (!isModern() || !sidebarLinks().length) return;
      e.preventDefault();
      if (pal && /\bui-open\b/.test(pal.className)) closePalette(); else openPalette();
    }
  }

  /* ---- back to top ---------------------------------------------------- */
  function onScroll() {
    if (!topBtn) return;
    var y = window.pageYOffset || root.scrollTop || 0;
    var show = y > 600;
    if (show !== /\bui-show\b/.test(topBtn.className)) topBtn.classList.toggle('ui-show', show);
  }
  function buildTop() {
    topBtn = el('button', 'ui-top', { type: 'button', title: 'Back to top', 'aria-label': 'Back to top' });
    topBtn.appendChild(el('i', 'fa fa-arrow-up', { 'aria-hidden': 'true' }));
    topBtn.addEventListener('click', function () { window.scrollTo(0, 0); });
    doc.body.appendChild(topBtn);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  function init() {
    safe(markCurrent);
    safe(buildToggle);
    safe(buildTop);
    safe(function () { doc.addEventListener('keydown', onKey, false); });
  }
  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', function () { safe(init); });
  else safe(init);
})();
