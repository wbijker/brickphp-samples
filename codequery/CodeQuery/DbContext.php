<?php

namespace CodeQuery;

use CodeQuery\Queryable\Queryable;
use CodeQuery\Queryable\SqlRootContext;
use CodeQuery\Schema\Table;
use CodeQuery\Schema\TableBuilder;
use CodeQuery\Sources\TableSource;

class DbContext
{

    /**
     * @param class-string $table
     * @return Queryable
     * @throws \Exception
     */
    function from(string $table): Queryable
    {
        $root = new SqlRootContext();
        /** @var Table $table */
        $source = $table::create($root);

        return new Queryable($source);
    }

}

