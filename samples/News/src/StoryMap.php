<?php

namespace Samples\News;

use BrickPHP\Js\Console;
use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\StatelessComponent;
use BrickPHP\VNode\VNode;

/**
 * Story-location section — Leaflet map plus a row of landmark "chips".
 * Clicking a chip pans the map to that landmark; the map opens
 * centered on the first entry in Landmarks::all().
 *
 * Stateless: the Leaflet is constructed each render and the click
 * handlers drive it via Js::invoke, so no component state is needed
 * to remember the active landmark across the POST round-trip.
 */
class StoryMap extends StatelessComponent
{
    protected function build(): VNode
    {
        $landmarks = Landmarks::all();
        $leaflet   = new Leaflet('story-map', $landmarks[0]['coords']);

        $leaflet->onClick(function($event) {
           Console::log("You have clicked the map, Sir", $event);
        });

        // Just declare the data — Leaflet stages each addMarker and
        // emits them inside its own setup block, so neither timing
        // nor duplication is our problem.
        foreach ($landmarks as $lm) {
            $leaflet->addMarker($lm['coords'][0], $lm['coords'][1], $lm['name']);
        }

        return UI::column()
            ->gap(Unit::rem(0.75))
            ->content(
                $leaflet,
                $this->landmarkRow($leaflet),
            );
    }

    private function landmarkRow(Leaflet $leaflet): UIElement
    {
        $chips = array_map(
            fn(array $lm) => UI::text($lm['name'])
                ->fontSize(FontSize::ExtraSmall)
                ->weight(FontWeight::Medium)
                ->color(Color::gray(700))
                ->color(Color::red(600), Pseudo::hover())
                ->paddingX(Unit::rem(0.6))
                ->paddingY(Unit::rem(0.3))
                ->background(Color::white())
                ->background(Color::gray(100), Pseudo::hover())
                ->rounded(Unit::rem(0.25))
                ->shadow(Shadow::Small)
                ->clickable()
                ->onClick(fn() => $leaflet->flyTo($lm['coords'], 14)),
            Landmarks::all(),
        );

        return UI::row()
            ->wrap()
            ->gap(Unit::rem(0.5))
            ->content(...$chips);
    }
}
