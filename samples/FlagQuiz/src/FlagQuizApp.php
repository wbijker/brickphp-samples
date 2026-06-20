<?php

namespace Samples\FlagQuiz;

use BrickPHP\State\SessionStateManager;
use BrickPHP\State\StateManager;
use BrickPHP\VNode\App;
use BrickPHP\VNode\VNode;

/**
 * Flagdle — a "name the flag against the clock" quiz.
 *
 * Ported from the "Flag Quiz" Claude Design project. The UI is built entirely
 * from BrickPHP's typed UI constructs (UI::column/row/text/image/…, Color,
 * Unit, FontSize, FontWeight, Shadow, Pseudo). The one bit of hand-written
 * code is a tiny keyboard script: the framework dispatches every keydown to the
 * server, so to keep typing client-side we handle the "pass" keys here and only
 * round-trip on those.
 */
class FlagQuizApp extends App
{
    public function title(): string
    {
        return 'Flagdle — Flag Quiz';
    }

    public function state(): StateManager
    {
        return new SessionStateManager();
    }

    protected function registerAssets(App $app): void
    {
        // A quick scale "pop" used to flag a changed value (the right / wrong
        // tallies). Re-keying the element on change restarts the animation.
        $app->addStyleInline(<<<'CSS'
            @keyframes fq-pop {
                0%   { transform: scale(1); }
                30%  { transform: scale(1.5); }
                100% { transform: scale(1); }
            }
            .fq-pop { display: inline-block; animation: fq-pop .45s ease; }
            CSS);

        // Keyboard is handled on the client; only Enter and the pass keys reach
        // the server. Enter already submits via the field's change event, so
        // typing never round-trips. Escape (anywhere) and Tab (while typing in
        // the input) click the hidden "next" button — a server request fires
        // only on those keys, never per keystroke.
        $app->addScriptInline(<<<'JS'
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape' && e.key !== 'Tab') return;
                var next = document.getElementById('fq-next');
                if (!next) return;
                if (e.key === 'Tab' && !(document.activeElement && document.activeElement.tagName === 'INPUT')) return;
                e.preventDefault();
                next.click();
            });
            JS);

        // Locations mode: Leaflet + a world-country GeoJSON overlay. The glue
        // below builds the map once, loads the polygons, colours each country
        // by quiz state (green = correct, red = wrong, blue = the one to name),
        // zooms to the current target, and dispatches the clicked country's ISO
        // code back to the server as a guess.
        $app->addStyle('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        $app->addScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js');
        $app->addScriptInline(<<<'JS'
            window.fqMap = null;
            window.fqLayers = {};     // iso2 -> Leaflet layer
            window.fqNames = {};      // iso2 -> country name (from the GeoJSON)
            window.fqState = { target: null, greens: [], reds: [], labels: false, autoZoom: true };

            function fqIso(feature) {
                var p = feature.properties || {};
                var iso = p.ISO_A2_EH || p.ISO_A2 || p.iso_a2 || p.wb_a2 || '';
                return (iso && iso !== '-99') ? String(iso).toLowerCase() : '';
            }
            function fqName(feature) {
                var p = feature.properties || {};
                return p.NAME_EN || p.NAME || p.ADMIN || '';
            }
            function fqStyleFor(iso) {
                var s = window.fqState || {};
                if ((s.greens || []).indexOf(iso) >= 0) return { color: '#16a34a', weight: 1,   fillColor: '#86efac', fillOpacity: 0.75 };
                if ((s.reds   || []).indexOf(iso) >= 0) return { color: '#dc2626', weight: 1,   fillColor: '#fca5a5', fillOpacity: 0.75 };
                if (s.target === iso)                   return { color: '#2563eb', weight: 2.5, fillColor: '#93c5fd', fillOpacity: 0.5 };
                return { color: '#cbd5e1', weight: 0.7, fillColor: '#f1f5f9', fillOpacity: 0.65 };
            }
            // Flag + name pinned at each country's centre. Permanent tooltips on
            // the polygons so they ride the layers and remove cleanly. Per-layer
            // idempotent so it works even when called before the GeoJSON loads.
            function fqSetLabels(on) {
                Object.keys(window.fqLayers).forEach(function (iso) {
                    var layer = window.fqLayers[iso];
                    var has = !!layer.getTooltip();
                    if (on && !has) {
                        var html = '<img src="https://flagcdn.com/w20/' + iso + '.png" alt=""><span>' + (window.fqNames[iso] || '') + '</span>';
                        layer.bindTooltip(html, { permanent: true, direction: 'center', className: 'fq-label', interactive: false, opacity: 1 });
                    } else if (!on && has) {
                        layer.unbindTooltip();
                    }
                });
            }
            window.fqApplyState = function (state) {
                if (state) window.fqState = state;
                var s = window.fqState;
                if (!window.fqMap) return;
                Object.keys(window.fqLayers).forEach(function (iso) {
                    window.fqLayers[iso].setStyle(fqStyleFor(iso));
                });
                fqSetLabels(!!s.labels);
                if (s.autoZoom && s.target && window.fqLayers[s.target]) {
                    try { window.fqMap.fitBounds(window.fqLayers[s.target].getBounds(), { padding: [50, 50], maxZoom: 6 }); } catch (e) {}
                }
            };
            window.fqGeo = null;  // cached GeoJSON so mode switches don't refetch
            function fqBuildLayers(map, geo, key) {
                L.geoJSON(geo, {
                    style: function (f) { return fqStyleFor(fqIso(f)); },
                    onEachFeature: function (f, layer) {
                        var iso = fqIso(f);
                        if (!iso) return;
                        window.fqLayers[iso] = layer;
                        window.fqNames[iso] = fqName(f);
                        layer.on('click', function () {
                            Brick.dispatch('country:pick', document.getElementById(key), iso);
                        });
                    }
                }).addTo(map);
                setTimeout(function () { map.invalidateSize(); window.fqApplyState(null); }, 60);
            }
            // Tear the map down when its screen unmounts (WorldMap::deleted()).
            // Without this the Leaflet instance + its DOM/panes can linger on a
            // detached container and resurface when the map screen is reopened.
            // fqGeo is kept cached so the next map build doesn't refetch.
            window.fqDestroyMap = function () {
                if (window.fqMap) {
                    try { window.fqMap.remove(); } catch (e) {}
                }
                window.fqMap = null;
                window.fqLayers = {};
                window.fqNames = {};
            };
            window.fqInitMap = function (key, url) {
                var el = document.getElementById(key);
                if (!el) return;
                // The map div is recreated when switching modes / replaying. If
                // the cached map is bound to a now-detached container, tear it
                // down and rebuild on the live element; otherwise just refresh.
                if (window.fqMap) {
                    if (window.fqMap.getContainer() === el) {
                        setTimeout(function () { window.fqMap.invalidateSize(); window.fqApplyState(null); }, 60);
                        return;
                    }
                    try { window.fqMap.remove(); } catch (e) {}
                    window.fqMap = null;
                    window.fqLayers = {};
                    window.fqNames = {};
                }
                var map = L.map(key, { minZoom: 1, worldCopyJump: true, attributionControl: false }).setView([25, 0], 2);
                window.fqMap = map;
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', { maxZoom: 8 }).addTo(map);
                if (window.fqGeo) {
                    fqBuildLayers(map, window.fqGeo, key);
                } else {
                    fetch(url).then(function (r) { return r.json(); }).then(function (geo) {
                        window.fqGeo = geo;
                        fqBuildLayers(map, geo, key);
                    });
                }
            };
            JS);

        // Map flag/name labels (Explore mode). Compact pill, no Leaflet arrow.
        $app->addStyleInline(<<<'CSS'
            .leaflet-tooltip.fq-label {
                display: flex; align-items: center; gap: 4px;
                padding: 1px 5px 1px 3px;
                background: #fff; border: 1px solid #e2e8f0; border-radius: 5px;
                box-shadow: 0 1px 3px rgba(0,0,0,.18);
                font: 600 10px/1.2 'Hanken Grotesk', system-ui, sans-serif; color: #1c1917;
                white-space: nowrap;
            }
            .leaflet-tooltip.fq-label::before { display: none; }
            .leaflet-tooltip.fq-label img { width: 16px; height: 11px; object-fit: cover; border-radius: 2px; }
            CSS);
    }

    protected function view(): VNode
    {
        return new FlagQuiz();
    }
}
