<?php

namespace Samples\FlagQuiz\Components;

use Closure;
use BrickPHP\Js\Js;
use BrickPHP\UI\Color;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\StatelessComponent;
use BrickPHP\VNode\VNode;

/**
 * The Leaflet world map for Locations mode. The map, the country GeoJSON
 * overlay, the per-country click wiring and the styling all live in the client
 * glue defined in {@see \Samples\FlagQuiz\FlagQuizApp::registerAssets()}; this
 * component just renders the map container, builds it once (created()), and on
 * every render pushes the current quiz state — which country to name (blue),
 * the ones already correct (green) and wrong (red) — so the map recolours and
 * zooms to the target. Clicking a country dispatches its ISO-2 code as a guess.
 */
class WorldMap extends StatelessComponent
{
    /**
     * Medium-detail Natural Earth countries (ISO_A2 in properties), served with
     * CORS. 50m covers the microstates the 110m set drops, so far more of the
     * 196 quiz countries can be highlighted and clicked.
     */
    private const GEOJSON_URL =
        'https://cdn.jsdelivr.net/gh/nvkelso/natural-earth-vector@v5.1.2/geojson/ne_50m_admin_0_countries.geojson';

    private const MAP_ID = 'fq-worldmap';

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

    protected function created(): void
    {
        Js::run(Js::invoke(Js::obj('window', 'fqInitMap'), Js::str(self::MAP_ID), Js::str(self::GEOJSON_URL)));
    }

    protected function build(): VNode
    {
        // Push the current colouring + zoom target to the (cached) map.
        $state = json_encode(new MapState(
            $this->targetIso,
            $this->greens,
            $this->reds,
            $this->labels,
            $this->autoZoom,
        ));
        Js::run(Js::invoke(Js::obj('window', 'fqApplyState'), $state));

        return UI::div()
            ->extend()
            ->minHeight(Unit::em(17.5))
            ->background(Color::stone(200))
            ->attr('id', self::MAP_ID)
            ->customEvent($this->onPick, new MapPickRegistration());
    }
}
