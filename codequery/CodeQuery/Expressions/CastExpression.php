<?php

namespace CodeQuery\Expressions;

use CodeQuery\Columns\BoolColumn;
use CodeQuery\Columns\Column;
use CodeQuery\Columns\FloatColumn;
use CodeQuery\Columns\IntColumn;
use CodeQuery\Columns\StringColumn;
use CodeQuery\Queryable\Query;

class CastExpression implements SqlExpression
{
    public function __construct(public SqlExpression $expr, public string $type)
    {
    }

    function toSql(): string
    {
        // ANSI SQL
        return "CAST(" . $this->expr->toSql() . " AS " . $this->type . ")";
    }

    static function asString(SqlExpression|Column $instance): StringColumn
    {
        return new StringColumn(new CastExpression(Query::toExpression($instance), "VARCHAR"));
    }

    static function asInt(SqlExpression|Column $instance): IntColumn
    {
        return new IntColumn(new CastExpression(Query::toExpression($instance), "INT"));
    }

    static function asFloat(SqlExpression|Column $instance): FloatColumn
    {
        return new FloatColumn(new CastExpression(Query::toExpression($instance), "FLOAT"));
    }

    static function asBool(SqlExpression|Column $instance): BoolColumn
    {
        return new BoolColumn(new CastExpression(Query::toExpression($instance), "BOOLEAN"));
    }

    static function asDate(SqlExpression|Column $instance): Column
    {
        return new Column(new CastExpression(Query::toExpression($instance), "DATE"));
    }

    static function asTimestamp(SqlExpression|Column $instance): Column
    {
        return new Column(new CastExpression(Query::toExpression($instance), "TIMESTAMP"));
    }

}