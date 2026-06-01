<?php

namespace App\Db;

use CodeQuery\Columns\IntColumn;
use CodeQuery\Columns\StringColumn;
use CodeQuery\Schema\Table;
use CodeQuery\Schema\TableBuilder;

class Category extends Table
{
    public IntColumn $id;
    public StringColumn $name;


    function buildTable(TableBuilder $builder): void
    {
        $builder->tableName("category");

        $this->id = $builder->int('id')->primaryKey()->autoIncrement();
        $this->name = $builder->string('name');
    }
}