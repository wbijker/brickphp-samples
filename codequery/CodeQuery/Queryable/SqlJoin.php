<?php

namespace CodeQuery\Queryable;

use CodeQuery\Expressions\SqlExpression;
use CodeQuery\Sources\SqlSource;

class SqlJoin
{
    public function __construct(
        public string        $type,
        public SqlSource     $source,
        public SqlExpression $on
    )
    {
    }
}