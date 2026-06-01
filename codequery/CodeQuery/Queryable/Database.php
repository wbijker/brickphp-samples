<?php

namespace CodeQuery\Queryable;

use CodeQuery\Schema\SqlContext;

class Database
{
    static function from(string $className): Query
    {
        $context = new SqlContext();
        $context->from = $context->createSourceFromType($className)->source;
        return new Query($context);
    }

    public static function scoped(callable $callable): Query
    {
        $context = new SqlContext();
        $query = new Query($context);
        $return = $context->invokeCallback($callable, $query);
        if ($return instanceof Query) {
            return $return;
        }
        return $query;
    }
}