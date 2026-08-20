<?php

namespace Samples\News;

use BrickPHP\Js\Js;
use BrickPHP\UI\Color;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\App;
use BrickPHP\VNode\StatelessComponent;
use BrickPHP\VNode\VNode;

/**
 * Leaflet map wrapper. Mirrors the most common one-way operations on
 * Leaflet's `L.map` (`setView`, `panTo`, `setZoom`, `zoomIn/Out`,
 * `flyTo`, `addMarker/Circle/Polygon/Popup`, `clearLayers`) and one
 * server-side event (`onClick`).
 *
 *   $this->leaflet = new Leaflet('story-map', [51.505, -0.09], 13);
 *   $this->leaflet->setView([48.8584, 2.2945], 14);
 *   $this->leaflet->onClick(fn(LeafletMouseEvent $e) =>
 *       Console::log('Map click at', $e->latlng->lat, $e->latlng->lng));
 *
 * Each instance is cached in JS at `window.leafLet[<key>]`. The L.map
 * itself is constructed once inside the `created()` ready-callback;
 * subsequent renders only emit method calls against the cached map.
 */
class Leaflet extends StatelessComponent
{
    /**
     * Namespaced event names dispatched via `Brick.dispatch` and
     * registered with `EventData::register`. Kept as class constants
     * so the literal `'leaflet:click'` only lives in one spot.
     */
    public const EVENT_CLICK = 'leaflet:click';

    /** Dispatched when a GeoJSON feature is clicked; the callback gets its id. */
    public const EVENT_FEATURE = 'leaflet:feature';

    /** Prefix carved off when generating the matching native Leaflet event name. */
    private const EVENT_PREFIX = 'leaflet:';

    /**
     * The Leaflet library on the unpkg CDN, pinned. This is the single home for
     * the Leaflet asset URLs — any app that needs Leaflet (this wrapper, or the
     * raw-`L` world map in {@see \Samples\FlagQuiz\FlagQuizApp}) pulls them in
     * via {@see registerAssets()} rather than hard-coding the links itself.
     */
    private const VERSION = '1.9.4';
    private const CSS_URL = 'https://unpkg.com/leaflet@' . self::VERSION . '/dist/leaflet.css';
    private const JS_URL = 'https://unpkg.com/leaflet@' . self::VERSION . '/dist/leaflet.js';

    /** @var (callable(LeafletMouseEvent): void)|null Click handler set via onClick() */
    private $onClick = null;

    /** @var (callable(string): void)|null Feature-click handler set via onFeatureClick() (gets the id) */
    private $onFeatureClick = null;

    /** Tile layer URL — OpenStreetMap by default; override with tile(). */
    private string $tileUrl = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

    /** @var array<string,mixed> Options for the tile layer. */
    private array $tileOptions = [
        'maxZoom' => 19,
        'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    ];

    /** @var array<string,mixed> Options object passed to `L.map(key, …)`. */
    private array $mapOptions = [];

    /** @var (callable(mixed): mixed)|null Customises the container div (size, colours…). */
    private $containerStyler = null;

    /** Fence the view to one copy of the world — see {@see singleWorld()}. */
    private bool $singleWorld = false;

    /** @var array{url:string,opts:array<string,mixed>}|null GeoJSON overlay, built once in created(). */
    private ?array $geoJson = null;

    /**
     * Decorative `addMarker` / `addCircle` / `addPolygon` / `addPopup`
     * calls buffer their rendered JS-expression strings here. The
     * setup `Brick.ready` block in created() drains the buffer so the
     * additions land on the client AFTER the map exists, exactly once
     * per map (subsequent renders re-fill the buffer but created()
     * doesn't fire again, so nothing duplicates).
     *
     * @var string[]
     */
    private array $staged = [];

    /**
     * @param array{0: float, 1: float}|null $initialCoords Initial view
     *   center — applied once in `created()`. Subsequent renders skip
     *   the setView so post-click state isn't reset.
     */
    public function __construct(
        private string $key,
        private ?array $initialCoords = null,
        private int    $initialZoom = 14,
    )
    {
    }

    // ============================================================
    // Server-side events (forwarded via Brick.dispatch)
    // ============================================================

    /**
     * Fires when the user clicks the map. The callback receives a
     * `LeafletMouseEvent` carrying the geographic coordinate that was
     * clicked (forwarded from Leaflet's own click event).
     *
     * @param callable(LeafletMouseEvent): void $callback
     */
    public function onClick(callable $callback): self
    {
        $this->onClick = $callback;
        return $this;
    }

    /**
     * Handle a GeoJSON feature click server-side. The callback receives the
     * clicked feature's id (as extracted by `addGeoJson`'s `idProps`). Enables
     * the per-feature click wiring in {@see addGeoJson()}.
     *
     * @param callable(string): void $callback
     */
    public function onFeatureClick(callable $callback): self
    {
        $this->onFeatureClick = $callback;
        return $this;
    }

    // ============================================================
    // Map configuration (fluent; applied when the map is created)
    // ============================================================

    /**
     * Override the tile layer. Defaults to OpenStreetMap.
     *
     * @param array<string,mixed> $options tile-layer options (maxZoom, …)
     */
    public function tile(string $url, array $options = []): self
    {
        $this->tileUrl = $url;
        $this->tileOptions = $options;
        return $this;
    }

    /**
     * Options object for `L.map(key, …)` — minZoom, worldCopyJump,
     * attributionControl, and so on.
     *
     * @param array<string,mixed> $options
     */
    public function mapOptions(array $options): self
    {
        $this->mapOptions = $options;
        return $this;
    }

    /**
     * Hold the map to a single copy of the world.
     *
     * Left alone, a world map zoomed out far enough shows the world more than
     * once: tiles repeat sideways forever, while an overlay drawn from GeoJSON
     * exists exactly once, at its real longitudes. Together those give the view
     * this fixes — the one set of countries split across the two far edges of a
     * repeating basemap, open water in between.
     *
     * Enabled, the tiles stop repeating and the view is fenced to that one
     * world, so it can be neither doubled nor left behind. The zoom floor
     * follows the container: out as far as the whole world, and no further,
     * since past that point zooming only shrinks it into the middle. A map
     * wider than the world at that zoom simply centres it — Leaflet's own
     * behaviour once the bounds are smaller than the view.
     */
    public function singleWorld(bool $on = true): self
    {
        $this->singleWorld = $on;
        return $this;
    }

    /**
     * Customise the container div (size, background…). The callback receives
     * the div and returns it, e.g. `fn($d) => $d->minHeight(Unit::em(18))`.
     * Without it the div gets a default 320px height.
     *
     * @param callable(mixed): mixed $styler
     */
    public function container(callable $styler): self
    {
        $this->containerStyler = $styler;
        return $this;
    }

    // ============================================================
    // GeoJSON overlay (native choropleth support, driven from PHP)
    // ============================================================

    /**
     * Overlay a GeoJSON layer, fetched and built once when the map is created
     * (cached by URL, so re-mounts don't refetch). Per-feature styling and
     * labels are then pushed each render via {@see styleFeatures()},
     * {@see showTooltips()} and {@see fitToFeature()} — all data-driven, so no
     * hand-written map JS is needed. `$opts` is the runtime feature config:
     *
     *   idProps        string[]  feature properties to read the id from (first hit wins)
     *   nameProps      string[]  feature properties to read the label name from
     *   ids            string[]  allow-list — only these ids are drawn (omit for all)
     *   split          bool      draw each polygon of a multi-part feature as its
     *                            own layer, so anything anchored to a layer —
     *                            its label, its bounds — follows each part
     *                            rather than the whole scattered set
     *   defaultStyle   object    base path style for every feature
     *   tooltipTemplate string   label HTML, with `{id}` / `{name}` placeholders
     *   tooltipOptions object    Leaflet tooltip options (className, direction…)
     *
     * A feature click dispatches {@see EVENT_FEATURE} when a handler is set via
     * {@see onFeatureClick()}.
     *
     * @param array<string,mixed> $opts
     */
    public function addGeoJson(string $url, array $opts = []): self
    {
        if ($this->onFeatureClick !== null) {
            $opts['event'] = self::EVENT_FEATURE;
            $opts['dispatchId'] = $this->key;
        }
        $this->geoJson = ['url' => $url, 'opts' => $opts];
        return $this;
    }

    /**
     * Push this render's per-feature style overrides — a map of feature id →
     * style object. Any feature not listed reverts to the layer's default
     * style. Applied to the (cached) map, so it recolours in place.
     *
     * @param array<string,array<string,mixed>> $overrides
     */
    public function styleFeatures(array $overrides): void
    {
        $this->geoOp('styleFeatures', $overrides);
    }

    /**
     * Zoom the map to a feature's bounds — but only once per id: a repeat call
     * with the same id is a no-op, so an unrelated re-render never yanks the
     * user's current zoom. Use {@see clearFit()} to forget the last target.
     *
     * @param array<string,mixed> $options fitBounds options (padding, maxZoom…)
     */
    public function fitToFeature(string $id, array $options = []): void
    {
        $this->geoOp('fitToFeature', Js::str($id), $options);
    }

    /** Forget the last fitted feature, so the next {@see fitToFeature()} re-zooms. */
    public function clearFit(): void
    {
        $this->geoOp('clearFit');
    }

    /** Show or hide the per-feature tooltips (labels). */
    public function showTooltips(bool $on): void
    {
        $this->geoOp('showTooltips', $on);
    }

    /** Emit one `BrickLeaflet.<fn>(key, …)` call wrapped in Brick.ready. */
    private function geoOp(string $fn, mixed ...$args): void
    {
        Js::ready(Js::invoke(Js::obj('BrickLeaflet', $fn), Js::str($this->key), ...$args));
    }

    // ============================================================
    // One-way map operations (PHP → JS)
    // ============================================================

    /** @param array{0: float, 1: float} $coordinates */
    public function setView(array $coordinates, int $zoom): void
    {
        $this->emit(Js::invoke(Js::obj("map", 'setView'), $coordinates, $zoom));
    }

    /** @param array{0: float, 1: float} $coordinates */
    public function panTo(array $coordinates): void
    {
        $this->emit(Js::invoke(Js::obj("map", 'panTo'), $coordinates));
    }

    public function setZoom(int $zoom): void
    {
        $this->emit(Js::invoke(Js::obj("map", 'setZoom'), $zoom));
    }

    public function zoomIn(int $delta = 1): void
    {
        $this->emit(Js::invoke(Js::obj("map", 'zoomIn'), $delta));
    }

    public function zoomOut(int $delta = 1): void
    {
        $this->emit(Js::invoke(Js::obj("map", 'zoomOut'), $delta));
    }

    /** @param array{0: float, 1: float} $coordinates */
    public function flyTo(array $coordinates, ?int $zoom = null): void
    {
        $args = $zoom === null ? [$coordinates] : [$coordinates, $zoom];
        $this->emit(Js::invoke(Js::obj("map", 'flyTo'), ...$args));
    }

    /** Remove every Layer from the map. */
    public function clearLayers(): void
    {
        // map.eachLayer(function(l) { map.removeLayer(l) })
        Js::ready("map.eachLayer(function(l){map.removeLayer(l)})");
    }

    /**
     * Drop a marker — `L.marker([lat,lng]).addTo(map).bindPopup(html)`.
     * Popup is bound (revealed on click) but not auto-opened. Empty
     * `$html` skips the bind. Staged; emitted once inside created().
     */
    public function addMarker(float $lat, float $lng, string $html = ''): void
    {
        $marker = Js::invoke(Js::obj('L', 'marker'), [$lat, $lng]);
        $withMap = Js::invoke(Js::obj($marker, 'addTo'), "map");
        $this->staged[] = $html === ''
            ? $withMap
            : Js::invoke(Js::obj($withMap, 'bindPopup'), Js::str($html));
    }

    /** `L.circle([lat,lng], opts).addTo(map)`. Staged for created(). */
    public function addCircle(float $lat, float $lng, Circle $circle): void
    {
        $expr = Js::invoke(Js::obj('L', 'circle'), [$lat, $lng], $circle->toArray());
        $this->staged[] = Js::invoke(Js::obj($expr, 'addTo'), "map");
    }

    /**
     * `L.polygon([[lat,lng], …]).addTo(map)`. Staged for created().
     * @param array<array{0: float, 1: float}> $coords
     */
    public function addPolygon(array $coords): void
    {
        $poly = Js::invoke(Js::obj('L', 'polygon'), $coords);
        $this->staged[] = Js::invoke(Js::obj($poly, 'addTo'), "map");
    }

    /**
     * `L.popup().setLatLng([lat,lng]).setContent(content).openOn(map)`.
     * Staged for created().
     */
    public function addPopup(float $lat, float $lng, string $content): void
    {
        $popup = Js::invoke(Js::obj('L', 'popup'));
        $withLatLng = Js::invoke(Js::obj($popup, 'setLatLng'), [$lat, $lng]);
        $withContent = Js::invoke(Js::obj($withLatLng, 'setContent'), Js::str($content));
        $this->staged[] = Js::invoke(Js::obj($withContent, 'openOn'), "map");
    }

    // ============================================================
    // Lifecycle + DOM
    // ============================================================

    /**
     * First-render setup inside a single `Brick.ready` block:
     *
     *   window.leafLet["<key>"] = L.map("<key>");
     *   L.tileLayer(…).addTo(window.leafLet["<key>"]);
     *   (optional) window.leafLet["<key>"].setView([lat,lng], zoom);
     *
     * The click listener is NOT wired here — it rides on the node's
     * EventRegistration (see clickRegistration()), which the diff attaches
     * on add and detaches on remove/delete, so it only listens when a
     * handler is set.
     */
    protected function created(): void
    {
        $ref = $this->mapRef();

        if ($this->singleWorld) {
            // Three things make the world singular: tiles that don't repeat,
            // a view fenced to the world's own bounds, and — dropped rather
            // than set — worldCopyJump, whose whole job is to carry the view
            // over into the next copy. There isn't one to carry it to.
            $this->tileOptions['noWrap'] = true;
            $this->mapOptions['maxBounds'] = [[-90, -180], [90, 180]];
            $this->mapOptions['maxBoundsViscosity'] = 1;
            unset($this->mapOptions['worldCopyJump']);
        }

        $lines = [
            // var map = L.map("key", {options});
            Js::assign("var map ", Js::invoke(Js::obj('L', 'map'), Js::str($this->key), $this->mapOptions)),
            // window.leaflet[key] = map;
            Js::assign($ref, "map"),
            Js::invoke(
                Js::obj(
                    Js::invoke(Js::obj('L', 'tileLayer'), Js::str($this->tileUrl), $this->tileOptions),
                    'addTo',
                ),
                "map",
            ),
            Js::invoke(Js::obj("map", 'setView'), $this->initialCoords ?? [0, 0], $this->initialZoom),
        ];

        // The zoom floor depends on how wide the map ends up on screen, which
        // only the client knows — and which changes when the window does.
        if ($this->singleWorld) {
            $lines[] = Js::invoke(Js::obj('BrickLeaflet', 'singleWorld'), Js::str($this->key));
        }

        // GeoJSON overlay (built once): hand the config to the runtime, which
        // fetches, draws the features and wires their clicks.
        if ($this->geoJson !== null) {
            $lines[] = Js::invoke(
                Js::obj('BrickLeaflet', 'addGeoJson'),
                Js::str($this->key),
                Js::str($this->geoJson['url']),
                $this->geoJson['opts'],
            );
        }

        // Drain staged additions (markers, circles, polygons, popups)
        // into the same Brick.ready block, after map setup.
        foreach ($this->staged as $stmt) {
            $lines[] = $stmt;
        }

        Js::ready(...$lines);
    }

    /** Bracket-form `window.leafLet[<key>]` reference — safe for any string key. */
    private function mapRef(): string
    {
        return Js::obj('window', 'leafLet') . Js::index($this->key);
    }

    /** Emit a single map operation wrapped in Brick.ready. */
    private function emit(string $stmt): void
    {
        // add map
        Js::ready(
            Js::assign("var map ", $this->mapRef()),
            $stmt
        );
    }

    protected function build(): VNode
    {
        $map = UI::div();
        if ($this->containerStyler !== null) {
            $map = ($this->containerStyler)($map) ?? $map;
        } else {
            $map->width(Unit::full())->height(Unit::px(320))->background(Color::gray(100));
        }
        $map->attr('id', $this->key);

        // Only register the click when a handler is set, so the map never
        // posts an unhandled click. The registration owns the event name.
        if ($this->onClick !== null) {
            $map->customEvent($this->onClick, $this->clickRegistration());
        }

        // Feature clicks are wired per feature inside the runtime, so this
        // registration is a pure name-carrier (no diff wiring); it just tells
        // the server which handler answers an EVENT_FEATURE dispatch.
        if ($this->onFeatureClick !== null) {
            $map->customEvent($this->onFeatureClick, new LeafletFeatureRegistration(self::EVENT_FEATURE));
        }

        return $map;
    }

    /**
     * Registration that wires/unwires the map's click listener as the diff
     * adds and removes this node.
     */
    private function clickRegistration(): LeafletEventRegistration
    {
        $off = Js::invoke(Js::obj('map', 'off'), Js::str(self::leafletEvent(self::EVENT_CLICK)));

        return new LeafletEventRegistration(
            self::EVENT_CLICK,
            $this->mapRef(),
            $this->wireEvent(self::EVENT_CLICK),
            $off,
        );
    }

    /**
     * Build the JS that wires a single server-side event:
     *
     *   map.on('<leafletEvent>', function(event) {
     *       Brick.dispatch('<serverEvent>',
     *                     event.target.getContainer(),
     *                     event.latlng);
     *   });
     */
    private function wireEvent(string $serverEvent): string
    {
        $leafletEvent = self::leafletEvent($serverEvent);

        $dispatch = Js::invoke(
            Js::obj('Brick', 'dispatch'),
            Js::str($serverEvent),
            Js::invoke(Js::obj('event', 'target', 'getContainer')),
            Js::obj('event', 'latlng'),
        );

        return Js::invoke(
            Js::obj('map', 'on'),
            Js::str($leafletEvent),
            "function(event){{$dispatch}}",
        );
    }

    /** Native Leaflet event name for a namespaced server event — `leaflet:click` → `click`. */
    private static function leafletEvent(string $serverEvent): string
    {
        return substr($serverEvent, strlen(self::EVENT_PREFIX));
    }

    /**
     * Tear a map down: remove the `L.map` and drop its runtime state. Call from
     * the owning component's `deleted()` so re-opening the map builds a fresh
     * one rather than resurrecting a detached container. The GeoJSON URL cache
     * survives, so the next build doesn't refetch.
     */
    public static function teardown(string $key): void
    {
        Js::ready(Js::invoke(Js::obj('BrickLeaflet', 'destroy'), Js::str($key)));
    }

    /**
     * Register the Leaflet library assets: pull its CSS + JS from the unpkg CDN,
     * seed `window.leafLet` (the per-key map registry this wrapper caches
     * instances in), and install the {@see runtimeJs()} client runtime. Call
     * once from an App's `registerAssets()` — used by {@see \Samples\News\NewsApp}
     * for this wrapper and by {@see \Samples\FlagQuiz\FlagQuizApp} for its
     * GeoJSON world map, so the CDN links live in exactly one place.
     */
    public static function registerAssets(App $app): void
    {
        $app->addStyle(self::CSS_URL);
        $app->addScript(self::JS_URL);
        $app->addScriptInline('window.leafLet = {};');
        $app->addScriptInline(self::runtimeJs());
    }

    /**
     * The `BrickLeaflet` client runtime backing the native GeoJSON API. It owns
     * the per-feature complexity that used to live in bespoke app glue: fetching
     * (and caching) the GeoJSON, drawing the features, wiring their clicks to a
     * server dispatch, and applying data-driven styles / labels / fit that PHP
     * pushes each render. It is tolerant of call order (a per-render style push
     * can arrive before the layer has loaded) and idempotent, so the map
     * converges once the async fetch resolves.
     */
    private static function runtimeJs(): string
    {
        return <<<'JS'
        window.BrickLeaflet = (function () {
            var state = {};   // mapKey -> feature state
            var cache = {};   // url    -> parsed GeoJSON (shared across rebuilds)

            // How far the pointer may travel between press and release and
            // still count as a tap rather than a pan, and how long a tap waits
            // to see whether a second one turns it into a double-click.
            var DRAG_SLOP_PX = 6;
            var DOUBLE_TAP_MS = 250;

            // A lookup table keyed by feature id. Bare — no prototype — because
            // the ids are arbitrary strings and some collide with what every
            // object inherits: Austria is 'at', and a plain {} (or the []
            // an empty map arrives as) answers ['at'] with Array.prototype.at,
            // a function that then reads as that country's style. Leaflet can
            // make nothing of it and paints its own blue over the country.
            function table(source) {
                var out = Object.create(null);
                Object.keys(source || {}).forEach(function (k) { out[k] = source[k]; });
                return out;
            }

            function ensure(key) {
                return state[key] || (state[key] = {
                    idProps: [], nameProps: [], defaultStyle: {}, ids: null, split: false,
                    styles: table(), byId: table(), names: table(),
                    event: '', dispatchId: key,
                    template: '', tooltipOpts: { permanent: true, direction: 'center' },
                    tooltips: false, fit: null, fittedId: null, loaded: false,
                    watching: false, dragged: false, pending: null, pendingId: ''
                });
            }

            // Pointer position of a mouse, touch or pen event.
            function pointOf(e) {
                var t = (e.touches && e.touches[0]) || (e.changedTouches && e.changedTouches[0]) || e;
                return [t.clientX || 0, t.clientY || 0];
            }

            // Watch how the map is being pressed, so a click can be told apart
            // from the end of a gesture. Dragging the world is a press, a move
            // and a release, and the browser still reports that as a click on
            // whatever shape stayed under the pointer throughout. Where a
            // feature click is an answer rather than a nudge of the view, that
            // misread costs the player the question. Listeners go on the
            // container in the capture phase, ahead of Leaflet's own handling.
            function watchGestures(key, map) {
                var st = ensure(key);
                if (st.watching) return;
                st.watching = true;

                var el = map.getContainer(), x = 0, y = 0;
                el.addEventListener('pointerdown', function (e) {
                    var p = pointOf(e);
                    x = p[0];
                    y = p[1];
                    st.dragged = false;
                }, true);
                el.addEventListener('pointerup', function (e) {
                    var p = pointOf(e);
                    st.dragged = Math.abs(p[0] - x) > DRAG_SLOP_PX
                        || Math.abs(p[1] - y) > DRAG_SLOP_PX;
                }, true);
            }

            // A feature was clicked. Report it only if the click was meant:
            // not the tail of a pan, and not the opening half of a double-click
            // (which zooms). Holding the first tap for the length of a
            // double-click is what makes the second one able to cancel it.
            function featureTap(key, id) {
                var st = state[key];
                if (!st || !st.event || st.dragged) return;
                if (st.pending) {
                    clearTimeout(st.pending);
                    st.pending = null;
                    // Twice on the same country is a zoom, and reports nothing.
                    // On a different one the player simply moved on, so the new
                    // tap takes the old one's place below.
                    if (id === st.pendingId) return;
                }
                st.pendingId = id;
                st.pending = setTimeout(function () {
                    st.pending = null;
                    Brick.dispatch(st.event, document.getElementById(st.dispatchId), id);
                }, DOUBLE_TAP_MS);
            }

            function idOf(feature, props) {
                var p = feature.properties || {};
                for (var i = 0; i < props.length; i++) {
                    var v = p[props[i]];
                    if (v && v !== '-99') return String(v).toLowerCase();
                }
                return '';
            }

            function nameOf(feature, props) {
                var p = feature.properties || {};
                for (var i = 0; i < props.length; i++) if (p[props[i]]) return p[props[i]];
                return '';
            }

            // Rough size of a polygon, from the shoelace area of its outer ring.
            // Only ever compared against its siblings, so raw degrees are fine.
            function ringArea(polygon) {
                var ring = polygon[0], sum = 0;
                for (var i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                    sum += ring[j][0] * ring[i][1] - ring[i][0] * ring[j][1];
                }
                return Math.abs(sum / 2);
            }

            // Split every MultiPolygon into one Feature per polygon, biggest
            // first, sharing the original's properties. Leaflet otherwise draws
            // a multi-part feature as ONE layer, so anything anchored to that
            // layer belongs to the whole scattered set rather than to any part
            // of it — a label placed at its centre lands between the pieces,
            // out at sea. Per polygon, the label goes on the main landmass
            // because the biggest leads. Returns a new collection; the cached
            // source is left untouched.
            function explode(geo) {
                var out = [];
                (geo.features || []).forEach(function (f) {
                    if (!f.geometry || f.geometry.type !== 'MultiPolygon') {
                        out.push(f);
                        return;
                    }
                    f.geometry.coordinates.slice()
                        .sort(function (a, b) { return ringArea(b) - ringArea(a); })
                        .forEach(function (coordinates) {
                            out.push({
                                type: 'Feature',
                                properties: f.properties,
                                geometry: { type: 'Polygon', coordinates: coordinates }
                            });
                        });
                });
                return { type: 'FeatureCollection', features: out };
            }

            // One id can own several features (a mainland plus its offshore
            // territories), so the label goes on the first — the primary
            // landmass in every dataset we use — not on each fragment.
            function applyTooltips(key) {
                var st = state[key];
                if (!st || !st.template) return;
                Object.keys(st.byId).forEach(function (id) {
                    var layer = st.byId[id][0], has = !!layer.getTooltip();
                    if (st.tooltips && !has) {
                        var html = st.template.replace(/\{id\}/g, id).replace(/\{name\}/g, st.names[id] || '');
                        layer.bindTooltip(html, st.tooltipOpts);
                    } else if (!st.tooltips && has) {
                        layer.unbindTooltip();
                    }
                });
            }

            // The last zoom at which the whole world still fits the map —
            // past it, zooming out only shrinks the world into the middle of
            // the container, which is worth stopping at. Measured rather than
            // asked of map.getBoundsZoom, which clamps its answer to the
            // minimum zoom currently set and so could never lower one. The
            // CRS's scale() is the world's size in pixels at a zoom, square
            // in Mercator, so the shorter side of the map decides.
            function worldFloor(map) {
                var size = map.getSize(), fits = Math.min(size.x, size.y),
                    crs = map.options.crs, z = 0;
                while (z < 18 && crs.scale(z + 1) <= fits) z++;
                return z;
            }

            // Bounds covering every feature that shares an id.
            function boundsOf(layers) {
                var b = layers[0].getBounds();
                for (var i = 1; i < layers.length; i++) b.extend(layers[i].getBounds());
                return b;
            }

            // Re-apply the desired state to a loaded map: recolour every feature,
            // sync labels, and zoom to the target once per id.
            function apply(key) {
                var st = state[key], map = window.leafLet[key];
                if (!st || !map || !st.loaded) return;
                Object.keys(st.byId).forEach(function (id) {
                    var style = st.styles[id] || st.defaultStyle;
                    st.byId[id].forEach(function (layer) { layer.setStyle(style); });
                });
                applyTooltips(key);
                if (st.fit && st.byId[st.fit.id] && st.fit.id !== st.fittedId) {
                    try { map.fitBounds(boundsOf(st.byId[st.fit.id]), st.fit.opts); } catch (e) {}
                    st.fittedId = st.fit.id;
                }
            }

            function buildLayer(key, geo) {
                var st = ensure(key), map = window.leafLet[key];
                if (!map) return;
                if (st.event) watchGestures(key, map);
                if (st.split) geo = explode(geo);
                L.geoJSON(geo, {
                    // Unidentifiable features, and (when an allow-list is set)
                    // anything outside it, are never drawn at all — so they
                    // can't be styled, labelled or clicked.
                    filter: function (f) {
                        var id = idOf(f, st.idProps);
                        return !!id && (!st.ids || st.ids[id] === true);
                    },
                    style: function (f) { return st.styles[idOf(f, st.idProps)] || st.defaultStyle; },
                    onEachFeature: function (f, layer) {
                        var id = idOf(f, st.idProps);
                        if (!id) return;
                        if (st.byId[id]) st.byId[id].push(layer);
                        else { st.byId[id] = [layer]; st.names[id] = nameOf(f, st.nameProps); }
                        if (st.event) {
                            layer.on('click', function () { featureTap(key, id); });
                        }
                    }
                }).addTo(map);
                st.loaded = true;
                // Leaflet needs a settle tick after the container is sized.
                setTimeout(function () { map.invalidateSize(); apply(key); }, 60);
            }

            return {
                addGeoJson: function (key, url, opts) {
                    var st = ensure(key);
                    opts = opts || {};
                    st.idProps = opts.idProps || [];
                    st.nameProps = opts.nameProps || [];
                    st.defaultStyle = opts.defaultStyle || {};
                    st.split = !!opts.split;
                    if (opts.ids && opts.ids.length) {
                        st.ids = table();
                        opts.ids.forEach(function (id) { st.ids[String(id).toLowerCase()] = true; });
                    }
                    st.event = opts.event || '';
                    st.dispatchId = opts.dispatchId || key;
                    st.template = opts.tooltipTemplate || '';
                    if (opts.tooltipOptions) st.tooltipOpts = opts.tooltipOptions;
                    if (cache[url]) buildLayer(key, cache[url]);
                    else fetch(url).then(function (r) { return r.json(); }).then(function (geo) {
                        cache[url] = geo; buildLayer(key, geo);
                    });
                },
                // Set the zoom floor to the level that shows the whole world,
                // and keep it in step as the map is resized. The bounds fence
                // and the non-repeating tiles come from the map options; this
                // is the part that has to measure the container first.
                singleWorld: function (key) {
                    var map = window.leafLet[key];
                    if (!map) return;
                    var hold = function () {
                        var z = worldFloor(map);
                        map.setMinZoom(z);
                        if (map.getZoom() < z) map.setZoom(z);
                    };
                    hold();
                    map.on('resize', hold);
                },
                styleFeatures: function (key, overrides) {
                    ensure(key).styles = table(overrides);
                    apply(key);
                },
                fitToFeature: function (key, id, opts) {
                    ensure(key).fit = { id: id, opts: opts || {} };
                    apply(key);
                },
                clearFit: function (key) {
                    var st = ensure(key); st.fit = null; st.fittedId = null;
                },
                showTooltips: function (key, on) {
                    ensure(key).tooltips = !!on;
                    apply(key);
                },
                destroy: function (key) {
                    var map = window.leafLet[key], st = state[key];
                    // A tap still waiting out its double-click window would
                    // otherwise land on a map that no longer exists.
                    if (st && st.pending) clearTimeout(st.pending);
                    if (map) { try { map.remove(); } catch (e) {} }
                    delete window.leafLet[key];
                    delete state[key];
                }
            };
        })();
        JS;
    }
}
