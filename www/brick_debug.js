// Brick debug runtime — dev-only tooling, served as a static external script.
// The server references it (<script src="/brick_debug.js">) only on dev-mode
// pages (Config::$development), so the inspector below runs unconditionally;
// the wireframe-only behaviour self-gates on window.__BRICK_WIREFRAME, which
// the server sets true only for ?wireframe=true renders.

// Inspector. Plain clicks pass through to the app; ctrl/cmd-click on a tagged
// element logs its construct/component label + source file:line to the console
// and navigates to the editor URL built from window.__BRICK_EDITOR_URL,
// substituting {file}/{line}/{col}. If the template is empty (no editor.url
// configured) the navigation step is skipped and only the console line emits.
//
// Also installs a "w" keybind that flips ?wireframe= on/off via a full page
// reload (the wireframe transform runs server-side, so a fresh GET is the right
// way to toggle). Ignored when focus is in an editable field, or a modifier is held.
(function () {
  function buildHref(file, line, col) {
    var tpl = window.__BRICK_EDITOR_URL;
    if (!tpl || !file) return null;
    return tpl
      .split('{file}').join(file)
      .split('{line}').join(line || '1')
      .split('{col}').join(col || '1');
  }
  document.addEventListener('click', function (e) {
    if (!e.ctrlKey && !e.metaKey) return;
    var el = e.target && e.target.closest ? e.target.closest('[data-wf-label]') : null;
    if (!el) return;
    e.preventDefault();
    e.stopPropagation();
    var label = el.getAttribute('data-wf-label') || '?';
    var file = el.getAttribute('data-wf-file');
    var line = el.getAttribute('data-wf-line');
    var loc = file ? (file + (line ? ':' + line : '')) : '(unknown)';
    console.log('%c' + label, 'font-weight:bold;color:#a06010', '@', loc);
    var href = buildHref(file, line, 1);
    if (href) window.location.href = href;
  }, true);
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'w' && e.key !== 'W') return;
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    var t = document.activeElement;
    if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable)) return;
    e.preventDefault();
    var url = new URL(window.location.href);
    if (url.searchParams.get('wireframe') === 'true') {
      url.searchParams.delete('wireframe');
    } else {
      url.searchParams.set('wireframe', 'true');
    }
    window.location.href = url.toString();
  });
})();

// Wireframe hover-highlight — only active in wireframe mode. Tracks the
// innermost tagged element under the cursor and toggles .brick-wf-hover for a
// translucent background highlight. Plain (non-modifier) clicks are swallowed
// too — wireframe view is read-only. Ctrl/cmd-click stays unhandled here; the
// inspector above logs it.
(function () {
  if (!window.__BRICK_WIREFRAME) return;
  var active = null;
  function clear() { if (active) { active.classList.remove('brick-wf-hover'); active = null; } }
  document.addEventListener('mousemove', function (e) {
    var el = e.target && e.target.closest ? e.target.closest('[data-wf-label]') : null;
    if (el === active) return;
    clear();
    if (el) { el.classList.add('brick-wf-hover'); active = el; }
  }, true);
  document.addEventListener('mouseleave', clear, true);
  document.addEventListener('click', function (e) {
    if (e.ctrlKey || e.metaKey) return;
    var el = e.target && e.target.closest ? e.target.closest('[data-wf-label]') : null;
    if (!el) return;
    e.preventDefault();
    e.stopPropagation();
  }, true);
})();
