<?php

namespace Samples\Docs\Sections;

use BrickPHP\UI\Color;
use BrickPHP\UI\FontSize;
use BrickPHP\UI\Pseudo;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\StatelessComponent;
use BrickPHP\VNode\VNode;
use HeroIcons\HeroIcons;
use Samples\Docs\Components\CodeWindow;
use Samples\Docs\Components\FeatureTile;
use Samples\Docs\Components\PHPCode;
use Samples\Docs\Components\PHPIcon;

/**
 * Seven-tile feature grid sitting on a light slate-50 surface. Each
 * tile is a FeatureTile with an icon, headline, body, and a code/
 * terminal/wireframe preview demonstrating the feature.
 */
class FeaturesSection extends StatelessComponent
{
    protected function build(): VNode
    {
        return UI::column()
            ->background(Color::slate(50))
            ->paddingY(Unit::px(40))
            ->paddingBottom(Unit::px(72))
            ->paddingX(Unit::px(24))
            ->content(
                UI::container()
                    ->maxWidth(Unit::px(1100))
                    ->marginX(Unit::auto())
                    ->width(Unit::full())
                    ->content(
                        UI::grid(1)
                            ->columns(2, Pseudo::md())
                            ->columns(3, Pseudo::lg())
                            ->gap(Unit::px(32))
                            ->content(
                                $this->allInOne(),
                                $this->noGlue(),
                                $this->hmr(),
                                $this->styling(),
                                $this->wireframe(),
                                $this->debugging(),
                                $this->uiElements(),
                            ),
                    ),
            );
    }

    private function allInOne(): VNode
    {
        $code = <<<'PHP'
        public function state() { ... }
        public function styling() { ... }
        public function render() { ... }
        PHP;
        return (new FeatureTile(
            HeroIcons::CircleStack('none', 1.5, 'currentColor', ''),
            'All in one place',
            'Routing, state, components, events, styling — every concern lives in one PHP codebase. No context-switching between languages or repos.',
        ))->preview($this->codeBox('App.php', $code));
    }

    private function noGlue(): VNode
    {
        $code = <<<'PHP'
        UI::div(
            $db->query('SELECT * FROM users')
        );
        PHP;
        return (new FeatureTile(
            HeroIcons::LinkSlash('none', 1.5, 'currentColor', ''),
            'No glue — all PHP',
            'Skip the API layer. Skip the serialization. Skip the type duplication. Your UI talks to your data directly because it lives in the same process.',
        ))->preview($this->codeBox('UserList.php', $code));
    }

    private function hmr(): VNode
    {
        $indicator = UI::row()
            ->gap(Unit::px(8))
            ->alignMiddle()
            ->padding(Unit::px(24))
            ->content(
                UI::container()
                    ->width(Unit::px(8))
                    ->height(Unit::px(8))
                    ->roundedFull()
                    ->background(Color::orange(500)),
                UI::text('HMR Active: src/Pages/Home.php')
                    ->fontSize(FontSize::ExtraSmall)
                    ->color(Color::orange(400)),
            );

        return (new FeatureTile(
            HeroIcons::Bolt('none', 1.5, 'currentColor', ''),
            'Hot module reloading',
            'Save a PHP file, watch the browser update in place — without losing state. HMR built in, no Vite or Webpack config required.',
        ))->preview(
            (new CodeWindow())->tab('terminal', '⚡')->content($indicator),
        );
    }

    private function styling(): VNode
    {
        $code = <<<'PHP'
        UI::div()
          ->padding(Unit::large())
          ->color(Color::orange());
        PHP;
        return (new FeatureTile(
            HeroIcons::PaintBrush('none', 1.5, 'currentColor', ''),
            'Styling out of the box',
            'A complete utility CSS system is bundled and lex-scanned from your source. Write semantic methods like padding(Unit::large()).',
        ))->preview($this->codeBox('Button.php', $code));
    }

    private function wireframe(): VNode
    {
        // Intentionally NOT a CodeWindow — this preview is a wireframe
        // sketch, not a code/terminal artifact. The dashed-border treatment
        // is the visual point.
        $wireframe = UI::column()
            ->bordered()
            ->dashed()
            ->borderColor(Color::orange(400))
            ->padding(Unit::px(8))
            ->background(Color::slate(950))
            ->rounded(Unit::rounded())
            ->content(
                UI::row()->content(
                    UI::container()
                        ->background(Color::orange(500))
                        ->color(Color::white())
                        ->paddingX(Unit::px(4))
                        ->fontSize(FontSize::ExtraSmall)
                        ->content(UI::text('Button.php:24')),
                ),
                UI::container()
                    ->marginTop(Unit::px(8))
                    ->width(Unit::full())
                    ->height(Unit::px(32))
                    ->bordered()
                    ->borderColor(Color::orange(400))
                    ->background(Color::orange(500)),
            );

        return (new FeatureTile(
            HeroIcons::Squares2x2('none', 1.5, 'currentColor', ''),
            'Wireframe inspector',
            'Toggle an overlay that outlines every UI element with its name and source location. Click straight through to the file from the browser.',
        ))->preview($wireframe);
    }

    private function debugging(): VNode
    {
        $log = UI::column()
            ->gap(Unit::px(4))
            ->padding(Unit::px(24))
            ->content(
                UI::text('# Server Log 14:02:11')
                    ->fontSize(FontSize::ExtraSmall)
                    ->color(Color::slate(400)),
                UI::row()->gap(Unit::px(8))->alignMiddle()->content(
                    UI::text('[POST] /counter/update')
                        ->fontSize(FontSize::ExtraSmall)
                        ->color(Color::slate(300)),
                    UI::text('200 OK')
                        ->fontSize(FontSize::ExtraSmall)
                        ->color(Color::emerald(500)),
                ),
                UI::row()->gap(Unit::px(8))->alignMiddle()->content(
                    UI::text('Delta:')
                        ->fontSize(FontSize::ExtraSmall)
                        ->color(Color::slate(300)),
                    UI::text('+1')
                        ->fontSize(FontSize::ExtraSmall)
                        ->color(Color::orange(400)),
                    UI::text('(Morphing DOM)')
                        ->fontSize(FontSize::ExtraSmall)
                        ->color(Color::slate(300)),
                ),
            );

        return (new FeatureTile(
            HeroIcons::BugAnt('none', 1.5, 'currentColor', ''),
            'Debugging made easy',
            'Xdebug works out of the box. Server-rendered patches surface in the browser console so you can see exactly what changed between renders.',
        ))->preview(
            (new CodeWindow())->tab('console', '🐛')->content($log),
        );
    }

    private function uiElements(): VNode
    {
        $code = <<<'PHP'
        class HeaderElement extends UIElement {
          public string $title;
        }
        PHP;
        return (new FeatureTile(
            HeroIcons::ViewColumns('none', 1.5, 'currentColor', ''),
            'UI elements, not JS + CSS',
            'Express your interface through typed UIElements. No className strings, no template languages, no JSX. Refactor with IDE confidence.',
        ))->preview($this->codeBox('HeaderElement.php', $code));
    }

    /** Wrap a PHP snippet in a CodeWindow with the 🐘 filename tab. */
    private function codeBox(string $filename, string $code): VNode
    {
        return (new CodeWindow())
            ->tab($filename, new PHPIcon())
            ->content(new PHPCode($code));
    }
}
