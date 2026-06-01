<?php

namespace CodeQuery\Sources;


use CodeQuery\Schema\SqlContext;

class QuerySource extends SqlSource
{
    public function __construct(private SqlContext $context)
    {
    }

    function toSql(): string
    {
        return "({$this->context->toSql()}) {$this->alias}";
    }

}