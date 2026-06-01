<?php

namespace Samples\Docs\Sections;

use BrickPHP\UI\Color;
use BrickPHP\UI\Direction;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\FontWeight;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\Router;
use BrickPHP\UI\Svg;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\StatelessComponent;
use BrickPHP\VNode\VNode;
use Samples\Docs\Components\CodeWindow;
use Samples\Docs\Components\PHPCode;
use Samples\Docs\Components\PHPIcon;

/**
 * Landing-page hero — left column with title + tagline + CTAs, right
 * column with a CodeWindow showing the bootstrap `index.php`. The
 * background is a dark SVG polygon that tapers from full-height on the
 * left to 90% on the right.
 */
class HeroSection extends StatelessComponent
{
    protected function build(): VNode
    {
        // Stacking by hand (Layers doesn't expose z-order):
        //   1. Outer column is position:relative — the positioning context.
        //   2. SVG backdrop is position:absolute + inset:0 — fills the
        //      relative parent edge-to-edge.
        //   3. Foreground column is position:relative + z-index:1, which
        //      promotes it above the absolutely-positioned backdrop in
        //      the same stacking context (positioned siblings stack by
        //      source order *and* z-index; without an explicit z, the
        //      absolute child would tie at z-auto and paint on top).
        return UI::column()
            ->relative()
            ->width(Unit::full())
            ->overflow()
            ->content(
                $this->backdrop(),
                UI::column()
                    ->relative()
                    ->zIndex(1)
                    ->color(Color::white())
                    ->paddingY(Unit::px(40))
                    ->paddingBottom(Unit::px(72))
                    ->paddingX(Unit::px(24))
                    ->content(
                        UI::row()
                            ->maxWidth(Unit::px(1100))
                            ->marginX(Unit::auto())
                            ->width(Unit::full())
                            ->gap(Unit::px(64))
                            ->alignMiddle()
                            ->direction(Direction::column())
                            ->direction(Direction::row(), Pseudo::lg())
                            ->content(
                                $this->text(),
                                $this->code(),
                            ),
                    ),
            );
    }

    /**
     * Dark backdrop painted as an SVG polygon — left edge runs the full
     * height of the hero, right edge stops at 90% so the bottom slopes
     * gently upward. The SVG is absolutely positioned with inset:0 to
     * fill the relative hero outer; `preserveAspectRatio="none"` and
     * width/height 100% let the normalized 0–100 viewBox stretch non-
     * uniformly to match the box.
     */
    private function backdrop(): UIElement
    {
        return UI::svg()
            ->absolute()
            ->inset(Unit::none())
            ->width(Unit::full())
            ->height(Unit::full())
            ->viewBox(0, 0, 100, 100)
            ->svgWidth('100%')
            ->svgHeight('100%')
            ->attr('preserveAspectRatio', 'none')
            ->attr('style', 'display:block;')
            ->content(
                Svg::polygon('0,0 100,0 100,90 0,100')
                    ->fill(Color::slate(900)),
            );
    }

    private function text(): UIElement
    {
        return UI::column()
            ->grow()
            ->gap(Unit::px(24))
            ->alignLeft()
            ->content(
                UI::text('The Modern ')
                    ->fontSize(FontSize::SixXL)
                    ->weight(FontWeight::Bold)
                    ->color(Color::white())
                    ->attr('style', 'line-height:1;')
                    ->content(
                        UI::span('Server-Driven')->color(Color::orange(500)),
                        UI::text(' Framework.')
                            ->color(Color::white()),
                    ),
                UI::text('Build complex, interactive web applications with the simplicity of PHP. BrickPHP bridges the gap between server stability and client-side fluidity without the JS fatigue.')
                    ->fontSize(FontSize::Large)
                    ->color(Color::slate(400))
                    ->maxWidth(Unit::px(560)),
                UI::row()
                    ->gap(Unit::px(16))
                    ->paddingTop(Unit::px(8))
                    ->content(
                        UI::button('Get started')
                            ->background(Color::orange(500))
                            ->color(Color::white())
                            ->paddingX(Unit::px(32))
                            ->paddingY(Unit::px(16))
                            ->rounded(Unit::roundedLg())
                            ->fontSize(FontSize::Base)
                            ->weight(FontWeight::SemiBold)
                            ->borderNone()
                            ->clickable()
                            ->background(Color::orange(600), Pseudo::hover()),
                        UI::button('Browse API')
                            ->background(Color::transparent())
                            ->color(Color::white())
                            ->bordered()
                            ->borderColor(Color::slate(700))
                            ->paddingX(Unit::px(32))
                            ->paddingY(Unit::px(16))
                            ->rounded(Unit::roundedLg())
                            ->fontSize(FontSize::Base)
                            ->weight(FontWeight::SemiBold)
                            ->clickable()
                            ->background(Color::slate(800), Pseudo::hover())
                            ->onClick(fn() => Router::navigate('/api')),
                    ),
            );
    }

    private function code(): UIElement
    {
        $php = <<<'PHP'
        <?php

        use BrickPHP\Brick;
        use App\App;

        require 'vendor/autoload.php';

        Brick::run(App::class);
        PHP;

        return UI::column()
            ->grow()
            ->width(Unit::full())
            ->maxWidth(Unit::px(540))
            ->content(
                (new CodeWindow())
                    ->tab('index.php', new PHPIcon())
                    ->content(new PHPCode($php)),
            );
    }
}
