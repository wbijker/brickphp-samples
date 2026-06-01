<?php

namespace CodeQuery\Columns;

use CodeQuery\Expressions\BinaryExpression;
use CodeQuery\Expressions\ConstExpression;
use CodeQuery\Expressions\FunctionExpression;
use CodeQuery\Expressions\SqlExpression;

class IntColumn extends Column
{
    public function createAlias(SqlExpression $exp): static
    {
        return new IntColumn($exp);
    }

    private function toExp(int|IntColumn $value): SqlExpression
    {
        return is_int($value)
            ? new ConstExpression($value)
            : $value->exp;
    }

    function equals(int|IntColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::EQUAL, $this->toExp($value)));
    }

    function notEquals(int|IntColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::NOT_EQUAL, $this->toExp($value)));
    }

    function greaterThan(int|IntColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::GREATER, $this->toExp($value)));
    }

    function lessThan(int|IntColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::LESS, $this->toExp($value)));
    }

    function greaterOrEqual(int|IntColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::GREATER_OR_EQUAL, $this->toExp($value)));
    }

    function lessOrEqual(int|IntColumn $value): BoolColumn
    {
        return new BoolColumn(new BinaryExpression($this->exp, BinaryExpression::LESS_OR_EQUAL, $this->toExp($value)));
    }

    function multiply(int|IntColumn $value): IntColumn
    {
        return new IntColumn(new BinaryExpression($this->exp, BinaryExpression::MULTIPLY, $this->toExp($value)));
    }

    function divide(int|IntColumn $value): IntColumn
    {
        return new IntColumn(new BinaryExpression($this->exp, BinaryExpression::DIVIDE, $this->toExp($value)));
    }

    function add(int|IntColumn $value): IntColumn
    {
        return new IntColumn(new BinaryExpression($this->exp, BinaryExpression::ADD, $this->toExp($value)));
    }

    function subtract(int|IntColumn $value): IntColumn
    {
        return new IntColumn(new BinaryExpression($this->exp, BinaryExpression::SUBTRACT, $this->toExp($value)));
    }

    function modulo(int|IntColumn $value): IntColumn
    {
        return new IntColumn(new BinaryExpression($this->exp, BinaryExpression::MODULO, $this->toExp($value)));
    }

    function bitwiseAnd(int|IntColumn $value): IntColumn
    {
        return new IntColumn(new BinaryExpression($this->exp, BinaryExpression::BITWISE_AND, $this->toExp($value)));
    }

    function bitwiseOr(int|IntColumn $value): IntColumn
    {
        return new IntColumn(new BinaryExpression($this->exp, BinaryExpression::BITWISE_OR, $this->toExp($value)));
    }

    function bitwiseXor(int|IntColumn $value): IntColumn
    {
        return new IntColumn(new BinaryExpression($this->exp, BinaryExpression::BITWISE_XOR, $this->toExp($value)));
    }

    public int $value;

    function convertFrom(mixed $val): void
    {
        $this->value = intval($val);
    }

    function count(): IntColumn
    {
        return new IntColumn(new FunctionExpression("count", [$this->exp]));
    }

    function sum(): IntColumn
    {
        return new IntColumn(new FunctionExpression("sum", [$this->exp]));
    }
}


