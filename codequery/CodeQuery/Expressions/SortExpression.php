<?php

namespace CodeQuery\Expressions;

use CodeQuery\Queryable\Query;

class SortExpression implements SqlExpression
{

    public function __construct(public SqlExpression $expr, public int $direction = Query::ORDER_ASC)
    {
    }

    function toSql(): string
    {
        return $this->expr->toSql() . " " . ($this->direction == Query::ORDER_ASC ? "ASC" : "DESC");
    }

}