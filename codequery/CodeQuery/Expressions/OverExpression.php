<?php

namespace CodeQuery\Expressions;

class OverExpression implements SqlExpression
{
    // $base should be a valid Window function base expression
    // dedicated window functions like ROW_NUMBER(), RANK(), etc.
    // and some aggregate functions can accept an OVER clause
    public function __construct(private SqlExpression  $base,
                                private ?SqlExpression $orderBy = null,
                                private ?SqlExpression $partitionBy = null,
                                private ?Frame         $frame = null)
    {
    }

    function toSql(): string
    {
        $q = "";
        if ($this->orderBy != null)
            $q .= "ORDER BY " . $this->orderBy->toSql();
        if ($this->partitionBy != null)
            $q .= "PARTITION BY " . $this->partitionBy->toSql();

        if ($this->frame != null) {
            // ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING
            $q .= " ".self::UnitToSql($this->frame->unit)." BETWEEN ".self::BoundToSql($this->frame->start)." PRECEDING AND ".self::BoundToSql($this->frame->end)." FOLLOWING";
        }

        return $this->base->toSql() . " OVER ($q)";
    }

    private static function UnitToSql(string $unit): string
    {
        return match ($unit) {
            Frame::UNIT_ROWS => "ROWS",
            Frame::UNIT_RANGE => "RANGE",
            Frame::UNIT_GROUPS => "GROUPS",
            default => throw new \InvalidArgumentException("Unknown frame unit: $unit"),
        };
    }

    private static function BoundToSql(FrameBound $bound): string
    {
        return match ($bound->bondType) {
            FrameBound::BOUND_UNBOUNDED => "UNBOUNDED",
            FrameBound::BOUND_CURRENT_ROW => "CURRENT ROW",
            FrameBound::BOUND_VALUE => "VALUE " . $bound->value,
            default => throw new \InvalidArgumentException("Unknown frame bound: " . $bound->bondType),
        };
    }


}