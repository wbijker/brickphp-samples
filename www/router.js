// Brick Router runtime — wires the browser History API to Brick and manages
// scroll restoration across SPA navigation, popstate, and full reloads.
// Registered as a static external script by BrickPHP\UI\Router::registerAssets().
(function () {
    if (window.__brickRouterAttached) return;
    window.__brickRouterAttached = true;

    // We manage scroll ourselves so the browser doesn't fight us on back/forward.
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    // Persist scroll positions across full page reloads via sessionStorage —
    // the in-memory map alone is wiped on refresh. Keyed per-URL so navigating
    // away and back (whether by SPA or by reload) lands at the same scroll.
    var STORAGE_KEY = '__brickScrollPositions';
    var scrollPositions = {};
    try {
        var raw = sessionStorage.getItem(STORAGE_KEY);
        if (raw) scrollPositions = JSON.parse(raw) || {};
    } catch (e) { /* quota / disabled — start with an empty map */ }

    function persist() {
        try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(scrollPositions)); }
        catch (e) { /* ignore — best-effort */ }
    }

    var currentKey = location.pathname + location.search;

    function keyOf() { return location.pathname + location.search; }

    // Continuously snapshot scrollY for the active URL. By keeping this fresh,
    // popstate (which fires AFTER location has already changed) can still save
    // the correct position under the OLD key — currentKey hasn't moved yet.
    var pendingFrame = false;
    window.addEventListener('scroll', function () {
        if (pendingFrame) return;
        pendingFrame = true;
        requestAnimationFrame(function () {
            pendingFrame = false;
            scrollPositions[currentKey] = window.scrollY;
            persist();
        });
    }, { passive: true });

    // pagehide is the reliable cross-browser "user is leaving" hook (mobile
    // Safari notably skips beforeunload). Fires on reload, navigation away,
    // and tab close — a final guaranteed write before the page is gone.
    window.addEventListener('pagehide', function () {
        scrollPositions[currentKey] = window.scrollY;
        persist();
    });

    // Restore on a rAF loop — the new DOM may not be tall enough yet when
    // navigation fires (patches apply after pushState, asynchronously after
    // popstate, and the body may still be parsing on initial page load).
    // Bail when scrollY matches target or we've waited long enough.
    function restoreFor(key) {
        var target = scrollPositions[key] || 0;
        var attempts = 0;
        function step() {
            var maxScroll = Math.max(0,
                document.documentElement.scrollHeight - window.innerHeight);
            var clamped = Math.min(target, maxScroll);
            window.scrollTo(0, clamped);
            attempts++;
            if (attempts < 30 && window.scrollY < target && maxScroll < target) {
                requestAnimationFrame(step);
            }
        }
        requestAnimationFrame(step);
    }

    // Navigation triggered by Router::navigate() reaches the browser via
    // history.pushState in `data.js`. The new page's height isn't settled
    // until patches are applied and laid out, so instead of scrolling inside
    // the pushState override we stash the target key and restore in response
    // to brick:patched, fired by brick.js once patches are applied.
    var pendingRestoreKey = null;
    window.addEventListener('brick:patched', function () {
        if (pendingRestoreKey !== null) {
            var key = pendingRestoreKey;
            pendingRestoreKey = null;
            restoreFor(key);
        }
    });

    // Initial restore — reload/direct hit on a URL we've seen before should
    // land back at the saved scroll position. The rAF loop tolerates the
    // body still being parsed.
    restoreFor(currentKey);

    // pushState changes location synchronously, but the runtime applies DOM
    // patches BEFORE running this (it's emitted in data.js and executed after
    // applyPatches). By now the new — often shorter — page is already on
    // screen and the browser has clamped window.scrollY, so reading it here
    // would save a corrupted position for the page we're leaving. The
    // outgoing scroll was already captured by the rAF snapshot above while the
    // old DOM was still on screen, so here we only roll currentKey forward and
    // queue the restore for the new URL.
    var origPush = history.pushState;
    history.pushState = function () {
        var ret = origPush.apply(this, arguments);
        var newKey = keyOf();
        if (newKey !== currentKey) {
            currentKey = newKey;
            pendingRestoreKey = newKey;
        }
        return ret;
    };

    // replaceState shouldn't be treated as navigation — just keep currentKey
    // in sync so subsequent scroll events save under the right URL.
    var origReplace = history.replaceState;
    history.replaceState = function () {
        var ret = origReplace.apply(this, arguments);
        currentKey = keyOf();
        return ret;
    };

    window.addEventListener('popstate', function () {
        // location has already changed; currentKey is still the OLD URL.
        scrollPositions[currentKey] = window.scrollY;
        persist();
        currentKey = keyOf();
        if (window.Brick && typeof Brick.refresh === 'function') {
            // Brick.refresh POSTs and the new DOM arrives asynchronously —
            // defer the restore so it lands after patches are applied.
            pendingRestoreKey = currentKey;
            Brick.refresh();
        } else {
            // No runtime to refresh against — restore immediately against
            // whatever DOM is already on the page.
            restoreFor(currentKey);
        }
    });
})();
