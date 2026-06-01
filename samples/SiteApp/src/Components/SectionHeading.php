<?php

namespace Samples\SiteApp\Components;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

class SectionHeading extends Component
{
    public function __construct(
        private string $title,
        private string $subtitle = '',
    ) {}

    protected function build(): VNode
    {
        $nodes = [
            UI::text($this->title)
                ->fontSize(FontSize::ThreeXL)
                ->weight(FontWeight::Bold)
                ->color(Color::slate(900)),
        ];

        if ($this->subtitle !== '') {
            $nodes[] = UI::text($this->subtitle)
                ->fontSize(FontSize::Base)
                ->color(Color::slate(500));
        }

        return UI::column()
            ->gap(Unit::small())
            ->paddingY(Unit::large())
            ->content(...$nodes);
    }
}
