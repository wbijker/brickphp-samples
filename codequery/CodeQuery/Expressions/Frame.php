<?php

namespace CodeQuery\Expressions;

class Frame
{
    const UNIT_ROWS = 'ROWS';
    const UNIT_RANGE = 'RANGE';
    const UNIT_GROUPS = 'GROUPS';

    public FrameBound $start;
    public FrameBound $end;

    private function __construct(public string $unit, ?FrameBound $start, ?FrameBound $end)
    {
        $this->start = $start ?? FrameBound::unbounded();
        $this->end = $end ?? FrameBound::unbounded();
    }

    public static function rows(?FrameBound $start, ?FrameBound $end): Frame
    {
        return new self(self::UNIT_ROWS, $start, $end);
    }

    public static function range(?FrameBound $start, ?FrameBound $end): Frame
    {
        return new self(self::UNIT_RANGE, $start, $end);
    }

    public static function groups(?FrameBound $start, ?FrameBound $end): Frame
    {
        return new self(self::UNIT_ROWS, $start, $end);
    }
}