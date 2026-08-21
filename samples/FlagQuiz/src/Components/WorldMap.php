<?php

namespace Samples\FlagQuiz\Components;

use Closure;
use BrickPHP\UI\Color;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Country;
use Samples\FlagQuiz\Attribute;
use Samples\FlagQuiz\LandmarkPlace;
use Samples\FlagQuiz\LandmarkPlaces;
use Samples\FlagQuiz\MapView;
use Samples\FlagQuiz\PlaceShapes;
use Samples\News\Leaflet;
use Samples\News\LeafletArea;
use Samples\News\LeafletLine;
use Samples\News\LeafletMouseEvent;
use Samples\News\LeafletOverlay;
use Samples\News\LeafletPin;

/**
 * The Leaflet world map for Locations mode. Built on the shared {@see Leaflet}
 * component's native GeoJSON API: the map, the country overlay, the per-country
 * click wiring and the styling all come from typed PHP calls — no hand-written
 * map JS. Each render pushes the current quiz state (the country to name = blue,
 * the ones already correct = green, wrong = red) as per-feature style overrides,
 * so the map recolours and zooms to the target. Clicking a country dispatches
 * its ISO-2 code, which the quiz reads as a move or an answer depending on the
 * question being asked. A quiz that asks *for* the place passes no target at
 * all — there, where the country is is the question, so nothing is highlighted
 * and nothing is zoomed to.
 *
 * Over the countries go the fact layers, pushed the same way each render: the
 * landmarks pinned with their pictures where they stand, the rivers traced,
 * the ranges and lakes filled in. Those come from {@see LandmarkPlaces} and
 * {@see PlaceShapes}, neither of which knows where everything is — a river
 * with no line and a range with no polygon simply aren't drawn, and the
 * country underneath carries the answer as it always did.
 */
class WorldMap extends Component
{
    /**
     * Medium-detail Natural Earth countries (ISO_A2 in properties), served with
     * CORS. 50m carries every one of the {@see Country::all()} catalogue —
     * including the microstates the 110m set drops — so all of them can be
     * highlighted and clicked. It also carries ~40 dependencies and territories
     * the quiz doesn't ask about (Greenland, Puerto Rico, Western Sahara…);
     * the `ids` allow-list below leaves those undrawn.
     */
    private const GEOJSON_URL =
        'https://cdn.jsdelivr.net/gh/nvkelso/natural-earth-vector@v5.1.2/geojson/ne_50m_admin_0_countries.geojson';

    private const MAP_ID = 'fq-worldmap';

    // Per-country path styles, as BrickLeaflet feature-style objects.
    private const DEFAULT_STYLE = ['color' => '#cbd5e1', 'weight' => 0.7, 'fillColor' => '#f1f5f9', 'fillOpacity' => 0.65];
    private const GREEN_STYLE   = ['color' => '#16a34a', 'weight' => 1,   'fillColor' => '#86efac', 'fillOpacity' => 0.75];
    private const RED_STYLE     = ['color' => '#dc2626', 'weight' => 1,   'fillColor' => '#fca5a5', 'fillOpacity' => 0.75];
    private const TARGET_STYLE  = ['color' => '#2563eb', 'weight' => 2.5, 'fillColor' => '#93c5fd', 'fillOpacity' => 0.5];

    /**
     * Laid over whichever country the pointer is on, keeping its own colour
     * underneath. Borders here are a hairline apart and half the map is one
     * shade of pale, so without this there is no telling which shape a click
     * would land on — least of all in Europe, where a wrong neighbour is a
     * pixel away.
     */
    private const HOVER_STYLE   = ['color' => '#0f172a', 'weight' => 2, 'fillOpacity' => 0.9];

    /**
     * Countries switched off. Invisible rather than absent: the shape stays on
     * the map and stays clickable, so picking a country still works with
     * nothing drawn.
     */
    private const HIDDEN_STYLE  = ['opacity' => 0, 'fillOpacity' => 0];

    /**
     * The fact layers, drawn over the countries. A river is a line and reads
     * as one; a range and a lake are areas and are filled. Each is a colour
     * you would expect the thing to be — water blue, rock brown — so the
     * layers tell themselves apart without a key.
     *
     * The waters are darker than the colour of water for a reason: the country
     * being asked about is filled pale blue ({@see TARGET_STYLE}), and a river
     * the blue of water drawn across it disappears into it. Pitched dark
     * enough to read against that fill, they still read as water against the
     * map's greys.
     */
    private const RIVER_STYLE    = ['color' => '#0c4a6e', 'weight' => 3, 'opacity' => 1];
    private const LAKE_STYLE     = ['color' => '#0c4a6e', 'weight' => 1.5, 'fillColor' => '#0ea5e9', 'fillOpacity' => 0.85];
    private const MOUNTAIN_STYLE = ['color' => '#78350f', 'weight' => 1.5, 'fillColor' => '#c2853f', 'fillOpacity' => 0.55];

    /**
     * @param string[] $greens ISO-2 codes answered correctly
     * @param string[] $reds   ISO-2 codes answered wrong
     * @param Closure  $onPick fn(string $iso): void
     * @param bool     $labels show each country's flag + name on the map (Explore)
     * @param bool     $autoZoom zoom the map to the target country on each render
     * @param Country[]   $factsOf    whose landmarks / rivers / ranges / lakes to
     *   draw — one country while one is being looked at, and every country in
     *   play when none is
     * @param Attribute[] $factLayers which of them to draw
     * @param ?MapView    $opensAt    where the map opens, once; null for the world
     * @param bool        $showCountries draw the country shapes and their labels
     */
    public function __construct(
        private string $targetIso,
        private array $greens,
        private array $reds,
        private Closure $onPick,
        private bool $labels = false,
        private bool $autoZoom = true,
        private array $factsOf = [],
        private array $factLayers = [],
        private ?MapView $opensAt = null,
        private bool $showCountries = true,
        private bool $namePlaces = true,
        private ?Closure $onLocate = null,
        private bool $fitToPlaces = false,
    ) {}

    /**
     * Every country's capital, keyed by ISO-2 — the `{sub}` line of the labels.
     *
     * @return array<string,string>
     */
    private static function capitals(): array
    {
        $out = [];
        foreach (Country::all() as $country) {
            $out[$country->code] = $country->capitalLabel();
        }
        return $out;
    }

    /**
     * The country's facts as things to draw. Empty unless a country and some
     * layers were asked for, and empty again for anything with no geometry
     * behind it — Natural Earth has no line for every river and no polygon for
     * every range, and a name it cannot draw simply isn't drawn. The country
     * stays highlighted underneath either way, so nothing goes missing; the
     * layer just adds detail where there is detail to add.
     *
     * @return LeafletOverlay[]
     */
    private function overlays(): array
    {
        if ($this->factsOf === [] || $this->factLayers === []) {
            return [];
        }

        $out = [];
        // A place shared by neighbours would otherwise be drawn once per
        // neighbour — the Nile five times over, the Alps eight — which is
        // wasted work and a stack of translucent fills the shared ones end up
        // darker for.
        $drawn = [];
        foreach ($this->factsOf as $country) {
            foreach ($this->factLayers as $fact) {
                foreach ($fact->valuesOf($country) as $name) {
                    $seen = $fact->value . '|' . Country::normalize($name);
                    if (isset($drawn[$seen])) {
                        continue;
                    }
                    $drawn[$seen] = true;

                    if ($fact === Attribute::Landmark) {
                        $place = LandmarkPlaces::for($country->code, $name);
                        if ($place !== null) {
                            $out[] = $this->pin($place);
                        }
                        continue;
                    }
                    $paths = PlaceShapes::for($fact, $country->code, $name);
                    if ($paths === []) {
                        continue;
                    }
                    $out[] = $fact === Attribute::River
                        ? new LeafletLine($paths, self::RIVER_STYLE)
                        : new LeafletArea($paths, $fact === Attribute::Lake ? self::LAKE_STYLE : self::MOUNTAIN_STYLE);
                }
            }
        }
        return $out;
    }

    /**
     * The rectangle holding everything drawn, as south-west and north-east
     * corners, or null if the overlays carry no coordinates. Read back off the
     * built overlays rather than recomputed from the catalogue, so what is
     * zoomed to is exactly what is on screen.
     *
     * @param LeafletOverlay[] $overlays
     * @return ?array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}
     */
    private function boundsOf(array $overlays): ?array
    {
        $lats = [];
        $lons = [];
        foreach ($overlays as $overlay) {
            $shape = $overlay->toArray();
            $points = match ($shape['kind']) {
                'pin' => [$shape['at']],
                'line' => array_merge(...$shape['paths']),
                default => array_merge(...$shape['rings']),
            };
            foreach ($points as [$lat, $lon]) {
                $lats[] = $lat;
                $lons[] = $lon;
            }
        }
        if ($lats === []) {
            return null;
        }
        // A single pin is a rectangle with no width, which fitBounds answers
        // with its closest zoom. A small margin round it lands somewhere the
        // surroundings are still recognisable.
        $margin = 0.25;
        return [
            [min($lats) - $margin, min($lons) - $margin],
            [max($lats) + $margin, max($lons) + $margin],
        ];
    }

    /**
     * What the zoom-once guard counts as "the same view": the countries and
     * layers being drawn. A new question changes it, a re-render does not.
     */
    private function placesKey(): string
    {
        return implode(',', array_map(fn(Country $c) => $c->code, $this->factsOf))
            . '|' . implode(',', array_map(fn(Attribute $a) => $a->value, $this->factLayers));
    }

    /**
     * A landmark, pinned: its picture over its name over a stalk down to the
     * spot. The picture is dropped where the article had none, leaving the
     * name — which is the half that says what you are looking at.
     *
     * Unless the name is the answer. Asked which landmark is shown, the pin is
     * the picture and the stalk alone, and the picture is the question. A
     * landmark with no picture then has nothing to show, so it is pinned as a
     * bare marker — somewhere to look, with the answer still to come.
     */
    private function pin(LandmarkPlace $place): LeafletPin
    {
        $image = $place->hasImage()
            ? '<img src="' . htmlspecialchars($place->image, ENT_QUOTES) . '" alt="">'
            : '';

        if (!$this->namePlaces) {
            return new LeafletPin(
                $place->lat,
                $place->lon,
                $image !== '' ? $image : '<span class="fq-pin-blank"></span>',
                'fq-pin',
            );
        }

        $name = '<span class="fq-pin-name">' . htmlspecialchars($place->name, ENT_QUOTES) . '</span>';
        return new LeafletPin($place->lat, $place->lon, $image . $name, 'fq-pin');
    }

    protected function deleted(): void
    {
        // The map screen unmounted (back to start / mode switch): tear the
        // Leaflet instance down so reopening builds a fresh one.
        Leaflet::teardown(self::MAP_ID);
    }

    protected function build(): VNode
    {
        $view = $this->opensAt ?? MapView::world();
        // Two ways of answering with the map, and only ever one of them at a
        // time. Pointing at a country dispatches which country; pointing at a
        // place dispatches where the pointer was, because the country a
        // landmark falls in is not what was asked. Wiring both would answer
        // the same click twice.
        $locate = $this->onLocate;
        $map = (new Leaflet(self::MAP_ID, $view->coordinates(), $view->zoom))
            // One world only. The countries are a GeoJSON overlay, so they
            // exist once however far out the basemap tiles repeat — zoomed
            // out, that showed the single set of them broken across the left
            // and right edges with nothing in the middle.
            ->singleWorld()
            ->mapOptions(['attributionControl' => false])
            ->tile('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', ['maxZoom' => 8])
            ->container(fn($div) => $div->extend()->minHeight(Unit::em(17.5))->background(Color::stone(200)));

        if ($locate !== null) {
            $map->onClick(fn(LeafletMouseEvent $event) => $locate($event->latlng->lat, $event->latlng->lng));
        } else {
            $map->onFeatureClick($this->onPick);
        }

        $map->addGeoJson(self::GEOJSON_URL, [
                'idProps' => ['ISO_A2_EH', 'ISO_A2', 'iso_a2', 'wb_a2'],
                'nameProps' => ['NAME_EN', 'NAME', 'ADMIN'],
                // Draw exactly the quiz catalogue and nothing else, so the map
                // holds the same countries the flag quiz asks about.
                'ids' => array_map(fn(Country $c) => $c->code, Country::all()),
                // A label sits on the first of its country's shapes. Split into
                // one shape per landmass, biggest first, and each label lands
                // on the mainland — undivided, the United States is one shape
                // reaching Alaska and its label sits out there. Only worth the
                // extra layers where labels actually show.
                'split' => $this->labels,
                'defaultStyle' => self::DEFAULT_STYLE,
                'hoverStyle' => self::HOVER_STYLE,
                // Flag, name and capital pill; styled by .leaflet-tooltip
                // .fq-label. The w40 flag is twice the size it's drawn at, so
                // it stays sharp on a retina screen.
                'tooltipTemplate' => '<img src="https://flagcdn.com/w40/{id}.png" alt="">'
                    . '<span class="fq-label-text">'
                    . '<span class="fq-label-name">{name}</span>'
                    . '<span class="fq-label-capital">{sub}</span>'
                    . '</span>',
                // Capitals come from the catalogue rather than the GeoJSON, and
                // only where the labels are actually drawn: in a quiz that asks
                // for a capital, shipping all 197 of them to the client would
                // put the answers in the page.
                'subtitles' => $this->labels ? self::capitals() : [],
                'tooltipOptions' => [
                    'permanent' => true, 'direction' => 'center',
                    'className' => 'fq-label', 'interactive' => false, 'opacity' => 1,
                ],
            ]);

        // Push this render's colouring. Precedence green > red > target: apply
        // target first, then reds, then greens last so a correct/wrong answer
        // keeps its colour. An empty target — a quiz that asks for the place,
        // where highlighting the country would be the answer — highlights nothing.
        $overrides = $this->targetIso === '' ? [] : [$this->targetIso => self::TARGET_STYLE];
        foreach ($this->reds as $iso) {
            $overrides[$iso] = self::RED_STYLE;
        }
        foreach ($this->greens as $iso) {
            $overrides[$iso] = self::GREEN_STYLE;
        }
        // Countries switched off: every one of them painted out, so the fact
        // layers stand on the bare basemap. Painted out rather than removed —
        // the shapes are still there to be clicked, so a country can still be
        // picked from the map, and turning them back on is a restyle rather
        // than a rebuild.
        if (!$this->showCountries) {
            foreach (Country::all() as $country) {
                $overrides[$country->code] = self::HIDDEN_STYLE;
            }
        }
        $map->styleFeatures($overrides);

        $map->showTooltips($this->labels && $this->showCountries);

        // The fact layers on top: the landmarks pinned where they are, the
        // rivers traced, the ranges and lakes filled in. Pushed every render,
        // so they follow whichever country is being looked at — see
        // Leaflet::setOverlays().
        $overlays = $this->overlays();
        $map->setOverlays(...$overlays);

        // Asked which landmark is shown, you have to be able to see it: at the
        // whole-world zoom the picture is four pixels across. Zoom to what was
        // drawn, once per question — the same guard fitToFeature uses, so
        // panning away and re-rendering doesn't drag the view back.
        if ($this->fitToPlaces && $overlays !== []) {
            $bounds = $this->boundsOf($overlays);
            if ($bounds !== null) {
                $map->fitToBounds($this->placesKey(), $bounds, ['padding' => [60, 60], 'maxZoom' => 7]);
            }
        }

        // Auto-zoom to the target — once per target (the runtime ignores a
        // repeat), so a re-render never resets the user's zoom. Off clears the
        // memory so turning it back on re-zooms.
        if ($this->autoZoom) {
            $map->fitToFeature($this->targetIso, ['padding' => [50, 50], 'maxZoom' => 6]);
        } else {
            $map->clearFit();
        }

        return $map;
    }
}
