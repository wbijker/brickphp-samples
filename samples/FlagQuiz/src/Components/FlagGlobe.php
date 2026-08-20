<?php

namespace Samples\FlagQuiz\Components;

use BrickPHP\UI\Shadow;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Palette;

/**
 * A desk globe with a flag standing on every country.
 *
 * The ball is one picture, drawn ahead of time by
 * {@see samples/FlagQuiz/tools/generate-globe.php} and saved as
 * {@see BALL}: every flag on its own real position, shrinking towards the rim
 * where the surface turns away, the near ones over the far ones, the world
 * tilted on its axis. It was built out of 157 positioned elements once — the
 * same arithmetic, run on every render of a screen where nothing about it ever
 * changed. Re-run the tool to change it.
 *
 * The stand is still made here, because it is three rectangles and a ring and
 * it should take the palette's colours: a ring around the ball, a neck, a foot.
 */
class FlagGlobe extends Component
{
    /** The ball, composed by the tool: a circle with transparent corners. */
    private const BALL = '/assets/images/flag-globe.png';

    public function __construct(private int $size = 190) {}

    protected function build(): VNode
    {
        return UI::column()
            ->noShrink()
            ->alignCenter()
            ->content(
                $this->sphere(),
                // The stand: a neck under the ring, and a foot under that.
                UI::container()
                    ->width(Unit::px(12))
                    ->height(Unit::px(16))
                    ->background(Palette::ink()),
                UI::container()
                    ->width(Unit::px((int)round($this->size * 0.42)))
                    ->height(Unit::px(11))
                    ->roundedFull()
                    ->background(Palette::ink()),
            );
    }

    /** The ball, and the ring it turns in. */
    private function sphere(): UIElement
    {
        return UI::container()
            ->relative()
            ->noShrink()
            ->width(Unit::px($this->size))
            ->height(Unit::px($this->size))
            ->content(
                // The ring stands a little proud of the ball: on a desk globe
                // the ring is the part that doesn't move, and the world leans
                // inside it.
                UI::container()
                    ->absolute()
                    ->inset(Unit::px(-7))
                    ->roundedFull()
                    ->bordered(3)
                    ->borderColor(Palette::subtle()),
                // Rounded and shadowed even though the picture is already a
                // circle: the shadow follows the box, so without the rounding
                // it would fall as a square behind a round thing.
                UI::image(self::BALL, 'A globe with the flag of every country on it')
                    ->width(Unit::full())
                    ->height(Unit::full())
                    ->roundedFull()
                    ->shadow(Shadow::Large),
            );
    }
}
