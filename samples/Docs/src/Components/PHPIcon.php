<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\Color;
use BrickPHP\UI\Svg;
use BrickPHP\UI\UI;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

/**
 * Cartoon ElePHPant — the PHP mascot rendered as a small SVG. Big
 * round body, oversized leaf-shaped ear, curled trunk, a single
 * white cartoon eye. Two purple tones (body + shadow) so it still
 * reads at favicon sizes.
 *
 * Default size is 18 px to drop into a CodeWindow filename tab.
 *
 *      ___
 *    /     \  ___
 *   ( o     \/   \
 *    \    ___ ___ /
 *    /   ||  ||
 *   ( __/   ||
 */
class PHPIcon extends Component
{
    private int $size = 18;

    public function size(int $px): static
    {
        $this->size = $px;
        return $this;
    }

    protected function build(): VNode
    {
        $body  = Color::purple(400);
        $shade = Color::purple(600);

        return UI::svg()
            ->viewBox(0, 0, 32, 32)
            ->svgWidth((string) $this->size)
            ->svgHeight((string) $this->size)
            ->attr('style', 'display:block;')
            ->content(
                // Back legs (sit behind body so the body overlaps cleanly)
                Svg::rect(13, 22, 3, 6)->rounded(1.5)->fill($shade),
                Svg::rect(22, 22, 3, 6)->rounded(1.5)->fill($shade),
                // Body — rounded oval to the right
                Svg::ellipse(19, 17, 9, 7)->fill($body),
                // Head — slightly smaller, on the left
                Svg::circle(11, 14, 6)->fill($body),
                // Ear — leaf shape on top of head, in the shaded tone
                Svg::path('M 12 9 Q 8 4 5 9 Q 7 13 12 11 Z')
                    ->fill($shade),
                // Trunk — curves from the lower-left of the head down
                // and curls forward into a small loop
                Svg::path('M 6 17 Q 3 20 4 24 Q 5 26 7 25 Q 7 23 6 22')
                    ->fill($body)
                    ->stroke($body)
                    ->strokeWidth('0.5')
                    ->strokeLinejoin('round'),
                // Eye — white sclera + black pupil
                Svg::circle(11, 13, 1.6)->fill('#ffffff'),
                Svg::circle(11, 13, 0.8)->fill('#0f172a'),
            );
    }
}
