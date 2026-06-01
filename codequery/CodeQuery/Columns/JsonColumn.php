<?php

namespace CodeQuery\Columns;


use CodeQuery\Expressions\FunctionExpression;
use CodeQuery\Expressions\SqlExpression;

class JsonColumn extends Column
{

    public function property(string $path): JsonColumn
    {
        // elem -> 'Unit'
    }

    public function exists(string $path): BoolColumn
    {
        // jsonb_exists(pfr."Data" -> 'Columns', 'Unit')
//        return new BoolColumn(new FunctionExpression('JSON_CONTAINS_PATH', [$this->exp, new ConstExpression('one'), new ConstExpression('$.'.$path)]));
    }

    public function typeOf(): StringColumn
    {
        // jsonb_typeof(pfr."Data" -> 'Columns') = 'array'
        return new StringColumn(new FunctionExpression('JSON_TYPE', [$this->exp]));
    }

    public function arrayElements(): JsonColumn
    {
        // jsonb_array_elements(pfr."Data" -> 'Columns'
    }

    public function convertFrom(mixed $val): void
    {
        // TODO: Implement convertFrom() method.
    }

    public function createAlias(SqlExpression $exp): static
    {
        // TODO: Implement createAlias() method.
        return new JsonColumn();
    }
}