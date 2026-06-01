<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

/**
 * Site footer — dark slate band with brand + copyright on the left and
 * link cluster on the right.
 */
class DocsFooter extends Component
{
    protected function build(): VNode
    {
        return UI::footer()
            ->background(Color::slate(900))
            ->borderTop(1)
            ->borderColor(Color::slate(800))
            ->paddingY(Unit::px(48))
            ->paddingX(Unit::px(24))
            ->content(
                UI::row()
                    ->maxWidth(Unit::px(1100))
                    ->marginX(Unit::auto())
                    ->width(Unit::full())
                    ->alignBetween()
                    ->content(
                        $this->brand(),
                        $this->links(),
                    ),
            );
    }

    private function brand(): UIElement
    {
        return UI::column()
            ->gap(Unit::px(8))
            ->content(
                UI::row()
                    ->gap(Unit::px(8))
                    ->alignMiddle()
                    ->content(
                        (new BrickLogo())->size(32),
                        UI::text('BrickPHP')
                            ->fontSize(FontSize::Large)
                            ->weight(FontWeight::Bold)
                            ->color(Color::white()),
                    ),
                UI::text('© 2026 BrickPHP. Documentation licensed under MIT.')
                    ->fontSize(FontSize::Small)
                    ->color(Color::slate(400)),
            );
    }

    private function links(): UIElement
    {
        return UI::row()
            ->gap(Unit::px(32))
            ->alignMiddle()
            ->content(
                $this->link('Documentation', '#'),
                $this->link('GitHub', '#'),
                $this->link('Discord', '#'),
                $this->link('X (Twitter)', '#'),
            );
    }

    private function link(string $label, string $href): UIElement
    {
        return UI::link($href, $label)
            ->fontSize(FontSize::Small)
            ->color(Color::slate(400))
            ->color(Color::white(), Pseudo::hover());
    }
}
