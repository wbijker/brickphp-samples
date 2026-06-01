<?php

namespace CodeQuery\Sources;

class TableSource extends SqlSource
{

    public function __construct(
        public string $tableName,
        public        $instance
    )
    {
    }

    function toSql(): string
    {
        return "`{$this->tableName}` {$this->alias}";
    }

}

