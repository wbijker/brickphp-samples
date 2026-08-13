<?php

namespace Samples\FlagQuiz\Components;

use Closure;
use BrickPHP\UI\Color;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Country;
use Samples\News\Leaflet;

/**
 * The Leaflet world map for Locations mode. Built on the shared {@see Leaflet}
 * component's native GeoJSON API: the map, the country overlay, the per-country
 * click wiring and the styling all come from typed PHP calls — no hand-written
 * map JS. Each render pushes the current quiz state (the country to name = blue,
 * the ones already correct = green, wrong = red) as per-feature style overrides,
 * so the map recolours and zooms to the target. Clicking a country dispatches
 * its ISO-2 code as a guess.
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
     * @param string[] $greens ISO-2 codes answered correctly
     * @param string[] $reds   ISO-2 codes answered wrong
     * @param Closure  $onPick fn(string $iso): void
     * @param bool     $labels show each country's flag + name on the map (Explore)
     * @param bool     $autoZoom zoom the map to the target country on each render
     */
    public function __construct(
        private string $targetIso,
        private array $greens,
        private array $reds,
        private Closure $onPick,
        private bool $labels = false,
        private bool $autoZoom = true,
    ) {}

    protected function deleted(): void
    {
        // The map screen unmounted (back to start / mode switch): tear the
        // Leaflet instance down so reopening builds a fresh one.
        Leaflet::teardown(self::MAP_ID);
    }

    protected function build(): VNode
    {
        $map = (new Leaflet(self::MAP_ID, [25, 0], 2))
            ->mapOptions(['minZoom' => 1, 'worldCopyJump' => true, 'attributionControl' => false])
            ->tile('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', ['maxZoom' => 8])
            ->container(fn($div) => $div->extend()->minHeight(Unit::em(17.5))->background(Color::stone(200)))
            ->onFeatureClick($this->onPick)
            ->addGeoJson(self::GEOJSON_URL, [
                'idProps' => ['ISO_A2_EH', 'ISO_A2', 'iso_a2', 'wb_a2'],
                'nameProps' => ['NAME_EN', 'NAME', 'ADMIN'],
                // Draw exactly the quiz catalogue and nothing else, so the map
                // holds the same countries the flag quiz asks about.
                'ids' => array_map(fn(Country $c) => $c->code, Country::all()),
                'defaultStyle' => self::DEFAULT_STYLE,
                // Compact flag + name pill; styled by .leaflet-tooltip.fq-label.
                'tooltipTemplate' => '<img src="https://flagcdn.com/w20/{id}.png" alt=""><span>{name}</span>',
                'tooltipOptions' => [
                    'permanent' => true, 'direction' => 'center',
                    'className' => 'fq-label', 'interactive' => false, 'opacity' => 1,
                ],
            ]);

        // Push this render's colouring. Precedence green > red > target: apply
        // target first, then reds, then greens last so a correct/wrong answer
        // keeps its colour.
        $overrides = [$this->targetIso => self::TARGET_STYLE];
        foreach ($this->reds as $iso) {
            $overrides[$iso] = self::RED_STYLE;
        }
        foreach ($this->greens as $iso) {
            $overrides[$iso] = self::GREEN_STYLE;
        }
        $map->styleFeatures($overrides);

        $map->showTooltips($this->labels);

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
