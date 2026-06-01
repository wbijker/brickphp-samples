<?php

namespace Samples\TodoApp;

use BrickPHP\UI\Color;
use BrickPHP\UI\Svg;
use BrickPHP\UI\UI;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;

final class Clock extends Component
{
    private const CENTER = 100;

    protected function build(): VNode
    {
        $faceBg = Color::white();
        $ink = Color::slate(800);
        $tickMinor = Color::slate(300);
        $accent = Color::red(500);

        $now = new \DateTime();
        $hours = (int)$now->format('G') % 12;
        $minutes = (int)$now->format('i');
        $seconds = (int)$now->format('s');

        $hourAngle = ($hours + $minutes / 60) * 30;
        $minuteAngle = ($minutes + $seconds / 60) * 6;
        $secondAngle = $seconds * 6;

        $children = [
            // Face
            Svg::circle(self::CENTER, self::CENTER, 92)
                ->fill($faceBg)
                ->stroke($ink)
                ->strokeWidth('3'),
        ];

        // 60 minute ticks
        for ($i = 0; $i < 60; $i++) {
            $isHour = $i % 5 === 0;
            $angle = $i * 6;
            $children[] = Svg::line(
                self::CENTER,
                self::CENTER - 88,
                self::CENTER,
                self::CENTER - ($isHour ? 76 : 82),
            )
                ->stroke($isHour ? $ink : $tickMinor)
                ->strokeWidth($isHour ? '3' : '1')
                ->strokeLinecap('round')
                ->transform("rotate($angle " . self::CENTER . ' ' . self::CENTER . ')');
        }

        // Hour hand
        $children[] = Svg::line(self::CENTER, self::CENTER + 12, self::CENTER, self::CENTER - 48)
            ->stroke($ink)
            ->strokeWidth('6')
            ->strokeLinecap('round')
            ->transform("rotate($hourAngle " . self::CENTER . ' ' . self::CENTER . ')', invalidate: true);

        // Minute hand
        $children[] = Svg::line(self::CENTER, self::CENTER + 16, self::CENTER, self::CENTER - 68)
            ->stroke($ink)
            ->strokeWidth('4')
            ->strokeLinecap('round')
            ->transform("rotate($minuteAngle " . self::CENTER . ' ' . self::CENTER . ')', invalidate: true);

        // Second hand
        $children[] = Svg::line(self::CENTER, self::CENTER + 22, self::CENTER, self::CENTER - 80)
            ->stroke($accent)
            ->strokeWidth('1.5')
            ->strokeLinecap('round')
            ->transform("rotate($secondAngle " . self::CENTER . ' ' . self::CENTER . ')', invalidate: true);

        // Center pin
        $children[] = Svg::circle(self::CENTER, self::CENTER, 5)->fill($ink);
        $children[] = Svg::circle(self::CENTER, self::CENTER, 2)->fill($accent);

        return UI::svg()
            ->viewBox(0, 0, 200, 200)
            ->size(Unit::px(160))
            ->content(...$children);
    }
}
