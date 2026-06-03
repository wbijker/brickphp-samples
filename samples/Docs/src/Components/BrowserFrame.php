<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use HeroIcons\HeroIcons;

/**
 * Mac-style browser chrome — three coloured traffic-light dots, a
 * lockable URL bar, and a slot for the page body. Used in the
 * "Reactive Components" section to frame a live preview.
 *
 *   (new BrowserFrame())
 *       ->url('localhost:8080/demo/counter')
 *       ->content(...);  // any UI to show inside the frame
 */
class BrowserFrame extends Component
{
    /** @var array<int, VNode|string|null> */
    private array $children = [];
    private string $url = 'localhost:8000';

    public function url(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function content(VNode|string|null ...$children): static
    {
        $this->children = array_merge($this->children, $children);
        return $this;
    }

    protected function build(): VNode
    {
        return UI::column()
            ->background(Color::white())
            ->rounded(Unit::roundedXl())
            ->shadow(Shadow::ExtraLarge)
            ->bordered()
            ->borderColor(Color::slate(200))
            ->overflow()
            ->content(
                $this->chrome(),
                UI::column()
                    ->background(Color::white())
                    ->content(...$this->children),
            );
    }

    private function chrome(): UIElement
    {
        return UI::row()
            ->background(Color::slate(100))
            ->borderBottom(1)
            ->borderColor(Color::slate(200))
            ->paddingX(Unit::px(16))
            ->paddingY(Unit::px(12))
            ->gap(Unit::px(16))
            ->alignMiddle()
            ->content(
                UI::row()->gap(Unit::px(8))->alignMiddle()->content(
                    UI::container()->width(Unit::px(12))->height(Unit::px(12))
                        ->roundedFull()->background(Color::red(400)),
                    UI::container()->width(Unit::px(12))->height(Unit::px(12))
                        ->roundedFull()->background(Color::amber(400)),
                    UI::container()->width(Unit::px(12))->height(Unit::px(12))
                        ->roundedFull()->background(Color::emerald(400)),
                ),
                $this->urlBar(),
            );
    }

    private function urlBar(): UIElement
    {
        return UI::row()
            ->grow()
            ->background(Color::white())
            ->bordered()
            ->borderColor(Color::slate(200))
            ->rounded(Unit::rounded())
            ->paddingX(Unit::px(12))
            ->paddingY(Unit::px(4))
            ->gap(Unit::px(8))
            ->alignMiddle()
            ->content(
                HeroIcons::LockClosed('none', 1.5, 'currentColor', '')
                    ->svgWidth('14')->svgHeight('14')
                    ->color(Color::slate(500)),
                UI::text($this->url)
                    ->fontSize(FontSize::ExtraSmall)
                    ->color(Color::slate(500)),
            );
    }
}
