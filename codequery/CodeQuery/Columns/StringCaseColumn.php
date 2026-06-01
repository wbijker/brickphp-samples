<?php

namespace CodeQuery\Columns;

use CodeQuery\Expressions\ConstExpression;
use CodeQuery\Expressions\SqlExpression;
use CodeQuery\Expressions\StringCaseExpression;

class StringCaseColumn
{
    /* @var SqlExpression[] */
    private array $whenThen = [];
    private ?SqlExpression $else = null;


    private static function resolve(StringColumn|string $value): SqlExpression
    {
        return is_string($value)
            ? new ConstExpression($value)
            : $value->exp;
    }

    public function when(BoolColumn $when, StringColumn|string $then): StringCaseColumn
    {
        $this->whenThen[] = [$when->exp, self::resolve($then)];
        return $this;
    }

    public function else(StringColumn|string $else): StringCaseColumn
    {
        $this->else = self::resolve($else);
        return $this;
    }

    public function end(): StringColumn
    {
        return new StringColumn(new StringCaseExpression(
            whenThen: $this->whenThen,
            else: $this->else
        ));
    }
}