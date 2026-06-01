<?php

namespace CodeQuery\Columns;

use CodeQuery\Expressions\AliasExpression;
use CodeQuery\Expressions\BinaryExpression;
use CodeQuery\Expressions\ConstExpression;
use CodeQuery\Expressions\SqlExpression;
use CodeQuery\Expressions\UnaryExpression;

class BoolColumn extends Column
{
    public function createAlias(SqlExpression $exp): static
    {
        return new BoolColumn($exp);
    }

    private function toExp(bool|BoolColumn $value): SqlExpression
    {
        return is_bool($value)
            ? new ConstExpression($value)
            : $value->exp;
    }

    function isTrue(): BoolColumn
    {
        return $this;
    }

    function isFalse(): BoolColumn
    {
        return new BoolColumn(new UnaryExpression($this->exp, UnaryExpression::NOT));
    }

    function and(bool|BoolColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::AND, $this->toExp($value)));
    }

    function or(bool|BoolColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::OR, $this->toExp($value)));
    }

    public bool $value;

    function convertFrom(mixed $val): void
    {
        $this->value = boolval($val);
    }
}

