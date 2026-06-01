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

    /** Prefix carved off when generating the matching native Leaflet event name. */
    private const EVENT_PREFIX = 'leaflet:';

    /** @var (callable(LeafletMouseEvent): void)|null Click handler set via onClick() */
    private $onClick = null;

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

        $lines = [
            // var map = L.map("key");
            Js::assign("var map ", Js::invoke(Js::obj('L', 'map'), Js::str($this->key))),
            // window.leaflet[key] = map;
            Js::assign($ref, "map"),
            Js::invoke(
                Js::obj(
                    Js::invoke(Js::obj('L', 'tileLayer'),
                        Js::str('https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
                        [
                            'maxZoom' => 19,
                            'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        ],
                    ),
                    'addTo',
                ),
                "map",
            ),
        ];

        $lines[] = Js::invoke(Js::obj("map", 'setView'), $this->initialCoords ?? [0, 0], $this->initialZoom);

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
        $map = UI::div()
            ->width(Unit::full())
            ->height(Unit::px(320))
            ->background(Color::gray(100))
            ->attr('id', $this->key);

        // Only register the click when a handler is set, so the map never
        // posts an unhandled click. The registration owns the event name.
        if ($this->onClick !== null) {
            $map->customEvent($this->onClick, $this->clickRegistration());
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
     * Pulls Leaflet's CSS + JS from the unpkg CDN, seeds
     * `window.leafLet`, and registers event-data hydrators for the
     * Leaflet-namespaced events so the server can turn the raw value
     * arrays into strongly typed event objects when handlers fire.
     */
    public static function registerAssets(App $app): void
    {
        $app->addStyle('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        $app->addScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js');
        $app->addScriptInline('window.leafLet = {};');
    }
}
