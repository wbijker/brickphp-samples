<?php

namespace CodeQuery\Expressions;

class BinaryExpression implements SqlExpression
{
    const EQUAL = "=";
    const NOT_EQUAL = "<>";
    const GREATER = ">";
    const GREATER_OR_EQUAL = ">=";
    const LESS = "<";
    const LESS_OR_EQUAL = "<=";

    const AND = "AND";
    const OR = "OR";
    const BITWISE_AND = "&";
    const BITWISE_OR = "|";
    const BITWISE_XOR = "^";


    const ADD = "+";
    const SUBTRACT = "-";
    const MULTIPLY = "*";
    const DIVIDE = "/";
    const MODULO = "%";


    public function __construct(
        public SqlExpression $left,
        public string        $operator,
        public SqlExpression $right
    )
    {
    }

    function toSql(): string
    {
        return "{$this->left->toSql()} {$this->operator} {$this->right->toSql()}";
    }

}
