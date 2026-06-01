<?php

namespace CodeQuery\Columns;

use CodeQuery\Expressions\SqlExpression;

abstract class Column
{
    public function __construct(public SqlExpression $exp)
    {
    }


    public abstract function convertFrom(mixed $val): void;
    public abstract function createAlias(SqlExpression $exp): static;
}