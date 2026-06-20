<?php

namespace Samples\FlagQuiz\Components;

use BrickPHP\UI\FontSize;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Answer;
use Samples\FlagQuiz\Palette;

/**
 * The last five attempts as a row of dots — green for a correct guess, red for
 * a wrong one, and an empty outline for slots not yet used.
 */
class AttemptHistory extends Component
{
    /** @param Answer[] $recent up to 5 outcomes (oldest→newest): Correct | Wrong */
    public function __construct(private array $recent) {}

    protected function build(): VNode
    {
        // Left-pad to five slots (Answer::Pending = an empty dot) so the strip
        // is a steady "last 5".
        $slots = array_slice(array_pad($this->recent, -5, Answer::Pending), -5);

        $dots = [];
        foreach ($slots as $r) {
            $dots[] = $this->dot($r);
        }

        return UI::row()
            ->alignMiddle()
            ->gap(Unit::px(8))
            ->content(
                UI::text('Last 5')->fontSize(FontSize::ExtraSmall)->uppercase()->color(Palette::labelMuted()),
                UI::row()->alignMiddle()->gap(Unit::px(5))->content(...$dots),
            );
    }

    private function dot(Answer $result): UIElement
    {
        // One chain so every class is harvested; Palette ternaries emit all
        // branches. Empty slots are a hollow outline (white fill, grey border).
        return UI::container()
            ->size(Unit::px(11))
            ->roundedFull()
            ->animated(160)
            ->bordered()
            ->background($result === Answer::Correct ? Palette::green() : ($result === Answer::Wrong ? Palette::red() : Palette::white()))
            ->borderColor($result === Answer::Correct ? Palette::green() : ($result === Answer::Wrong ? Palette::red() : Palette::border()));
    }
}
