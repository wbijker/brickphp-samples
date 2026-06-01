<?php

namespace CodeQuery\Expressions;

class FrameBound
{
    const BOUND_UNBOUNDED = 'UNBOUNDED';
    const BOUND_CURRENT_ROW = 'CURRENT_ROW';
    const BOUND_VALUE = "VALUE";

    private function __construct(public $bondType, public ?int $value = null)
    {
        // $bondType can be UNBOUNDED, CURRENT_ROW or value
        // $value is only used if bondType is value
    }

    public static function unbounded()
    {
        return new self(self::BOUND_UNBOUNDED);
    }

    public static function value(int $value)
    {
        return new self(self::BOUND_VALUE, $value);
    }

    public static function currentRow()
    {
        return new self(self::currentRow());
    }
}