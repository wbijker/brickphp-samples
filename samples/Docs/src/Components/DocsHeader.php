<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Router;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

/**
 * Fixed-position top navigation — 56-px tall, white surface, 1-px subtle
 * bottom border. Logo + wordmark on the left, primary nav links, Get
 * Started CTA on the right. The active link gets a 2-px orange underline.
 */
class DocsHeader extends Component
{
    public function __construct(private string $active = 'overview') {}

    protected function build(): VNode
    {
        return UI::nav()
            ->background(Color::white())
            ->borderBottom(1)
            ->borderColor(Color::slate(200))
            ->paddingX(Unit::px(24))
            ->height(Unit::px(56))
            ->width(Unit::full())
            ->content(
                UI::row()
                    ->maxWidth(Unit::px(1100))
                    ->marginX(Unit::auto())
                    ->width(Unit::full())
                    ->height(Unit::full())
                    ->alignMiddle()
                    ->alignBetween()
                    ->content(
                        $this->leftCluster(),
                        $this->rightCluster(),
                    ),
            );
    }

    private function leftCluster(): UIElement
    {
        return UI::row()
            ->gap(Unit::px(32))
            ->alignMiddle()
            ->content(
                UI::row()
                    ->gap(Unit::px(8))
                    ->alignMiddle()
                    ->clickable()
                    ->onClick(fn() => Router::navigate('/'))
                    ->content(
                        (new BrickLogo())->size(32),
                        UI::text('BrickPHP')
                            ->fontSize(FontSize::Large)
                            ->weight(FontWeight::Bold)
                            ->color(Color::slate(900)),
                    ),
                UI::row()
                    ->hidden()
                    ->flex(Pseudo::md())
                    ->gap(Unit::px(24))
                    ->alignMiddle()
                    ->content(
                        $this->navLink('Overview', '/',         'overview'),
                        $this->navLink('API',      '/api',      'api'),
                        $this->navLink('Docs',     '/docs',     'docs'),
                        $this->navLink('Showcase', '/showcase', 'showcase'),
                        $this->navLink('Github',   '/github',   'github'),
                    ),
            );
    }

    private function rightCluster(): UIElement
    {
        return UI::row()
            ->gap(Unit::px(16))
            ->alignMiddle()
            ->content(
                UI::button('Get started')
                    ->background(Color::orange(500))
                    ->color(Color::white())
                    ->paddingX(Unit::px(16))
                    ->paddingY(Unit::px(8))
                    ->rounded(Unit::rounded())
                    ->fontSize(FontSize::Small)
                    ->weight(FontWeight::Medium)
                    ->borderNone()
                    ->clickable()
                    ->background(Color::orange(600), Pseudo::hover()),
            );
    }

    private function navLink(string $label, string $href, string $key): UIElement
    {
        $isActive = $this->active === $key;

        $btn = UI::button($label)
            ->borderNone()
            ->background(Color::transparent())
            ->color($isActive ? Color::orange(500) : Color::slate(500))
            ->color(Color::slate(900), Pseudo::hover())
            ->paddingY(Unit::px(4))
            ->fontSize(FontSize::Base)
            ->weight($isActive ? FontWeight::SemiBold : FontWeight::Normal)
            ->clickable()
            ->onClick(fn() => Router::navigate($href));

        if ($isActive) {
            $btn = $btn
                ->borderBottom(2)
                ->borderColor(Color::orange(500));
        }

        return $btn;
    }
}
