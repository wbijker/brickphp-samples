<?php

namespace CodeQuery\Expressions;

class UnaryExpression implements SqlExpression
{
    const NOT = "NOT";

    public function __construct(
        private SqlExpression $exp,
        private string        $operator
    )
    {
    }

    function toSql(): string
    {
        return "{$this->operator} {$this->exp->toSql()}";
    }
}