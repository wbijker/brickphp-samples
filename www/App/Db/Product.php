<?php

namespace App\Db;

use CodeQuery\Columns\FloatColumn;
use CodeQuery\Columns\IntColumn;
use CodeQuery\Columns\StringColumn;
use CodeQuery\Schema\Table;
use CodeQuery\Schema\TableBuilder;

class Product extends Table
{
    public IntColumn $id;
    public StringColumn $name;
    public IntColumn $category_id;
    public FloatColumn $price;

    public function category(): Category
    {
        return $this->innerJoin(Category::class, fn(Category $c) => $this->category_id->equals($c->id));
    }

    function buildTable(TableBuilder $builder): void
    {
        $builder->tableName("product");

        $this->id = $builder->int("id")
            ->primaryKey()
            ->autoIncrement();

        $this->name = $builder->string("name");

        $this->category_id = $builder
            ->int("category_id")
            ->foreignKey(fn(Category $c) => $c->id);

        $this->price = $builder->float("price");
    }
}
