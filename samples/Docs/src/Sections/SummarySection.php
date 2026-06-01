<?php

namespace Samples\Docs\Sections;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\StatelessComponent;
use BrickPHP\VNode\VNode;

/**
 * Single-paragraph "what is this framework" band that sits between the
 * hero and the features grid. White background, thin bottom border.
 */
class SummarySection extends StatelessComponent
{
    protected function build(): VNode
    {
        return UI::column()
            ->background(Color::white())
            ->borderBottom(1)
            ->borderColor(Color::slate(200))
            ->paddingY(Unit::px(48))
            ->paddingX(Unit::px(24))
            ->content(
                UI::container()
                    ->maxWidth(Unit::px(1100))
                    ->marginX(Unit::auto())
                    ->width(Unit::full())
                    ->content(
                        UI::text('BrickPHP is a Server-Powered Web Applications framework. Everything runs natively on server: state, DOM diffing, CSS extraction — all. One coherent solution. No more npm install, no node_modules, no npm package updates, no separate building steps, no separate state management library, no CSS library, no JS framework. All just simple PHP.')
                            ->fontSize(FontSize::Large)
                            ->color(Color::slate(700))
                            ->center()
                            ->maxWidth(Unit::px(800))
                            ->marginX(Unit::auto()),
                    ),
            );
    }
}
