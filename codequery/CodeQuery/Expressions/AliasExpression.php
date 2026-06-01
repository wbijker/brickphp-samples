<?php

namespace CodeQuery\Expressions;

use CodeQuery\Queryable\Query;

class AliasExpression implements SqlExpression
{
    public function __construct(public SqlExpression $expr, public string $alias)
    {
    }

    function toSql(): string
    {
        return $this->expr->toSql() . " AS " . $this->alias;
    }
}