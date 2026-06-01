<?php

namespace CodeQuery\Expressions;

use CodeQuery\Sources\SqlSource;

class ColumnExpression implements SqlExpression
{
    public function __construct(private string $column, private SqlSource $source)
    {
    }

    function toSql(): string
    {
        return "{$this->source->alias}.{$this->column}";
    }
}
