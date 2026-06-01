<?php

namespace CodeQuery\Columns;

use CodeQuery\Expressions\BinaryExpression;
use CodeQuery\Expressions\ConstExpression;
use CodeQuery\Expressions\FunctionExpression;
use CodeQuery\Expressions\SqlExpression;

class FloatColumn extends Column
{

    public function createAlias(SqlExpression $exp): static
    {
        return new FloatColumn($exp);
    }

    private function toExp(float|FloatColumn $value): SqlExpression
    {
        return is_double($value)
            ? new ConstExpression($value)
            : $value->exp;
    }

    function equals(float|FloatColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::EQUAL, $this->toExp($value)));
    }

    function notEquals(float|FloatColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::NOT_EQUAL, $this->toExp($value)));
    }

    function greaterThan(float|FloatColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::GREATER, $this->toExp($value)));
    }

    function lessThan(float|FloatColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::LESS, $this->toExp($value)));
    }

    function greaterOrEqual(float|FloatColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::GREATER_OR_EQUAL, $this->toExp($value)));
    }

    function lessOrEqual(float|FloatColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::LESS_OR_EQUAL, $this->toExp($value)));
    }

    function multiply(float|FloatColumn $value): FloatColumn
    {
        return new FloatColumn(new BinaryExpression($this->exp, BinaryExpression::MULTIPLY, $this->toExp($value)));
    }

    function divide(float|FloatColumn $value): FloatColumn
    {
        return new FloatColumn(new BinaryExpression($this->exp, BinaryExpression::DIVIDE, $this->toExp($value)));
    }

    function add(float|FloatColumn $value): FloatColumn
    {
        return new FloatColumn(new BinaryExpression($this->exp, BinaryExpression::ADD, $this->toExp($value)));
    }

    function subtract(float|FloatColumn $value): FloatColumn
    {
        return new FloatColumn(new BinaryExpression($this->exp, BinaryExpression::SUBTRACT, $this->toExp($value)));
    }


    public bool $value;

    function convertFrom(mixed $val): void
    {
        $this->value = floatval($val);
    }

    function count(): IntColumn
    {
        return new IntColumn(new FunctionExpression("count", [$this->exp]));
    }

    function sum(): FloatColumn
    {
        return new FloatColumn(new FunctionExpression("sum", [$this->exp]));
    }
}

