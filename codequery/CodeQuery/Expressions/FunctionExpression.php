<?php

namespace CodeQuery\Expressions;

class FunctionExpression implements SqlExpression
{
    /**
     * @param string $name
     * @param SqlExpression[] $params
     */
    public function __construct(private string $name, private array $params)
    {
    }

    function toSql(): string
    {
        return $this->name . '(' . implode(", ", array_map(fn($e) => $e->toSql(), $this->params)) . ")";
    }
}