<?php

namespace CodeQuery\Columns;

use CodeQuery\Expressions\SqlExpression;
use CodeQuery\Expressions\StarExpression;

class DateTimeColumn extends Column
{
    public function createAlias(SqlExpression $exp): static
    {
        return new DateTimeColumn($exp);
    }

    static function now(): DateTimeColumn
    {
        return new DateTimeColumn(new StarExpression());
    }

    public function convertFrom(mixed $val): void
    {

    }
}