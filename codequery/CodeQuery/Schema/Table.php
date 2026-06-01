<?php

namespace CodeQuery\Schema;

use CodeQuery\Queryable\Query;
use CodeQuery\Queryable\SqlJoin;
use CodeQuery\Sources\SqlSource;

abstract class Table
{

    public function __construct(protected SqlContext $context)
    {
    }

    abstract function buildTable(TableBuilder $builder): void;


    // cache for storing joins to avoid duplication
    private $joins = [];

    /**
     * @template T
     * @param callable(T): bool $condition
     * @return T
     */
    protected function innerJoin(string $source, callable $condition)
    {
        $source = $this->context->createSourceFromType($source);
        $on = $this->context->invokeCallback($condition);

        $this->context->joins[] = new SqlJoin(
            "inner",
            $source->source,
            Query::toExpression($on)
        );
        return $source->instance;
    }

}