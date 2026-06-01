<?php

namespace CodeQuery\Expressions;


class ConstExpression implements SqlExpression
{
    public function __construct(public mixed $value)
    {
    }

    function toSql(): string
    {
        return match (gettype($this->value)) {
            "string" => "'" . $this->value . "'",
            "double", "integer" => (string)$this->value,
            "boolean" => $this->value ? "1" : "0",
            default => "NULL",
        };
    }
}

