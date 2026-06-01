<?php

namespace CodeQuery\Queryable;

use CodeQuery\Columns\BoolColumn;
use CodeQuery\Columns\Column;
use CodeQuery\Expressions\AliasExpression;
use CodeQuery\Expressions\ColumnExpression;
use CodeQuery\Expressions\SortExpression;
use CodeQuery\Expressions\SqlExpression;
use CodeQuery\Schema\SqlContext;
use CodeQuery\Sources\QuerySource;
use CodeQuery\Sources\SqlSource;
use CodeQuery\Sources\TableSource;

class Query
{
    public function __construct(private SqlContext $context)
    {
    }

    public function getContext(): SqlContext
    {
        return $this->context;
    }

    static function toExpression(SqlExpression|Column $value): SqlExpression
    {
        if ($value instanceof Column)
            return $value->exp;

        return $value;
    }

    public function from(string|object $from): Query
    {
        $source = $this->context->createSource($from);
        $this->context->from = $source->source;
        return $this;
    }

    public function scoped(callable $callable): Query
    {
        $this->context->invokeCallback($callable, $this);
        return $this;
    }

    // BoolColumn $predicate
    function where(callable|BoolColumn $callback): self
    {
        $condition = $callback instanceof BoolColumn
            ? $callback
            : $this->context->invokeCallback($callback, $this);

        if (!$condition instanceof BoolColumn) {
            throw new \InvalidArgumentException("Where condition must return an instance of " . BoolColumn::class . ", got " . gettype($condition));
        }
        $this->context->where[] = $condition->exp;
        return $this;
    }

    function groupBy(callable $callback): self
    {
        $group = $this->context->invokeCallback($callback, $this);
        // or array
        if (!$group instanceof Column) {
            throw new \InvalidArgumentException("Group by must return an instance of " . Column::class . ", got " . gettype($group));
        }
        $this->context->groupBy = [$group->exp];
        return $this;
    }

    const ORDER_ASC = 0;
    const ORDER_DESC = 1;

    function orderBy(callable|Column $callback, int $direction = self::ORDER_ASC): self
    {
        $order = $callback instanceof Column
            ? $callback
            : $this->context->invokeCallback($callback, $this);

        // or array
        if (!$order instanceof Column && !$order instanceof SqlExpression) {
            throw new \InvalidArgumentException("Order by must return an instance of " . Column::class . ", got " . gettype($order));
        }
        $this->context->orderBy[] = new SortExpression(self::toExpression($order), $direction);
        return $this;
    }

    function orderByDesc(callable|Column $callback): self
    {
        return $this->orderBy($callback, self::ORDER_DESC);
    }

    function innerJoin(string|object $source, BoolColumn|callable $on): self
    {
        // innerJoin just after a select will create a subquery
        if (!empty($this->context->select)) {
            $q = $this->context->createSubQuery();
            return $q->innerJoin($source, $on);
        }

        $source = $this->context->createSource($source);

        $condition = is_callable($on)
            ? $this->context->invokeCallback($on, $this)
            : $on;

        $this->context->joins[] = new SqlJoin(
            "inner",
            $source->source,
            self::toExpression($condition)
        );
        return $this;
    }

    function select(callable|object $callback): self
    {
        if (!empty($this->context->select)) {
            $q = $this->context->createSubQuery();
            return $q->select($callback);
        }

        $selection = is_callable($callback)
            ? $this->context->invokeCallback($callback, $this)
            : $callback;

        if (gettype($selection) != 'object') {
            throw new \InvalidArgumentException("Selection must return an object, got " . gettype($selection));
        }
        // convert object to array
        // make sure each property is a SqlExpression
        // and select the underlying expression
        $select = [];
        foreach ((array)$selection as $key => $value) {
            if (!$value instanceof Column) {
                throw new \InvalidArgumentException("Selection property '$key' must be an instance of " . Column::class . ", got " . gettype($value));
            }
            $select[$key] = new AliasExpression($value->exp, $key);
        }

        $this->context->select = $select;
        $this->context->selectType = $selection;
        return $this;
    }

    function fetch(?callable $callback): array
    {
        echo $this->toSql() . "\n";

        // $results is a mock of the database results
        $results = [
            [
                'categoryId' => 1,
                'count' => 1,
                'row' => 1,
                'name' => 'Product 1',
            ],
            [
                'categoryId' => 2,
                'count' => 22,
                'row' => 2,
                'name' => 'Product 2',
            ]
        ];

        $filled = array_map(function ($row) {
            $instance = clone $this->context->selectType;
            foreach ($row as $key => $value) {
                if (property_exists($instance, $key)) {
                    $prop = clone $instance->$key;
                    $instance->$key = $prop;
                    if ($prop instanceof Column)
                        $prop->convertFrom($value);
                }
            }
            return $instance;
        }, $results);

        if (!$callback)
            return $filled;

        // if callback is provided, map the results to the callback
        return array_map($callback, $filled);
    }

    function toSql(): string
    {
        return $this->context->toSql();
    }


}