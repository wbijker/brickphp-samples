<?php

namespace CodeQuery\Schema;


use CodeQuery\Columns\Column;
use CodeQuery\Expressions\BinaryExpression;
use CodeQuery\Expressions\ColumnExpression;
use CodeQuery\Expressions\ConstExpression;
use CodeQuery\Expressions\SqlExpression;
use CodeQuery\Queryable\Query;
use CodeQuery\Queryable\SqlJoin;
use CodeQuery\Sources\QuerySource;
use CodeQuery\Sources\SqlSource;

// created SQl context. Holding sources, select, where, group by, order by, joins
// each block defined it's own context.
// The scope of a SQL query
// Each table is created from a SqlContext

class SqlContext
{
    // combination of all sources used in this query
    // dictionary<String, SqlSource>
    // from + joins
    /**
     * @var array<SourceType> $sources
     */
    public array $sources = [];

    private function sourceByType(string $type): ?SourceType
    {
        foreach ($this->sources as $sourceType) {
            if ($sourceType->type === $type) {
                return $sourceType;
            }
        }
        return null;
    }

    private function sourceByInstance(object $instance): ?SourceType
    {
        foreach ($this->sources as $sourceType) {
            if ($sourceType->instance === $instance) {
                return $sourceType;
            }
        }
        return null;
    }

    /**
     * @var SqlExpression[] $select
     */
    public array $select = [];

    public ?object $selectType = null;

    /**
     * @var SqlExpression[] $where
     */
    public array $where = [];
    /**
     * @var SqlExpression[] $groupBy
     */
    public array $groupBy = [];
    /**
     * @var SqlExpression[] $orderBy
     */
    public array $orderBy = [];

    /**
     * @var SqlJoin[] $joins
     */
    public array $joins = [];

    public SqlSource $from;


    private int $aliasCounter = 0;

    public function nextAlias(): string
    {
        return "t" . $this->aliasCounter++;
    }

    public function createSourceFromType(string $search): SourceType
    {
        // search by type
        $hit = $this->sourceByType($search);
        if ($hit) {
            return $hit;
        }

        // create table
        $table = new $search($this);
        if (!$table instanceof Table) {
            throw new \InvalidArgumentException("Class $search is not part of the query not is it a table");
        }
        $builder = new TableBuilder($table);
        // invoke the builder to build the table structure
        $table->buildTable($builder);
        $source = $builder->source;
        $source->setAlias($this->nextAlias());

        $sourceType = new SourceType($search, $source, $table);
        $this->sources[] = $sourceType;
        return $sourceType;
    }

    public function createSourceFromInstance(object $search): SourceType
    {
        $hit = $this->sourceByInstance($search);
        if ($hit) {
            return $hit;
        }

        throw new \InvalidArgumentException("Instance of " . get_class($search) . " is not part of the query");
    }

    private function createSourceFromQuery(Query $source): SourceType
    {
        $context = $source->getContext();
        return new SourceType(get_class($context->selectType), $context->from, $context->selectType);
    }

    public function createSource(string|object $source): SourceType
    {
        // String source = table class
        if (is_string($source))
            return $this->createSourceFromType($source);

        // Query instance = SubQuery / const / etc.
        if ($source instanceof Query)
            return $this->createSourceFromQuery($source);

        // Object source = refer to scoped instance
        if (is_object($source))
            return $this->createSourceFromInstance($source);

        throw new \InvalidArgumentException("Source must be a string or an object, got " . gettype($source));
    }

    public function createSubQuery(): Query
    {
        $context = new SqlContext();
        $context->from = new QuerySource($this);
        $context->from->setAlias($context->nextAlias());

        $last = $this->selectType;
        // loop through all properties of $last
        foreach ((array)$last as $key => $value) {
            if (!$value instanceof Column) {
                throw new \InvalidArgumentException("Selection property '$key' must be an instance of " . Column::class . ", got " . gettype($value));
            }
            $last->$key = $value->createAlias(new ColumnExpression($key, $context->from));
        }

        $context->sources[] = new SourceType(get_class($this->selectType), $context->from, $last);
        return new Query($context);
    }

    public function invokeCallback(callable $callback, ?Query $query = null): mixed
    {
        // using reflection to get the parameters of the callback
        $reflection = new \ReflectionFunction($callback);
        $params = $reflection->getParameters();

        $filledParams = array_map(function ($param) use ($query) {
            // Inject the query itself if asked for
            $type = $param->getType()->getName();

            if ($type == Query::class) {
                return $query;
            }
            $source = $this->createSourceFromType($type);
            return $source->instance;
        }, $params);

        // invoke the callback with the filled parameters
        $res = $callback(...$filledParams);
        return $res;
    }

    private function reduceWhere(array $where): SqlExpression
    {
        if (empty($where)) {
            return new ConstExpression(true);
        }

        $first = $where[0];

        return array_reduce(
            array_slice($where, 1),
            fn(SqlExpression $prev, SqlExpression $current) => new BinaryExpression($prev, "AND", $current),
            $first
        );
    }

    function toSql(): string
    {
        $sql = "SELECT\n";

        $columns = empty($this->select)
            ? ["*"]
            : array_map(fn(SqlExpression $expr) => $expr->toSql(), $this->select);

        $sql .= implode(",\n", $columns) . "\n";

        $sql .= "FROM\n";
        $sql .= $this->from->toSql() . "\n";

        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $sql .= strtoupper($join->type) . " JOIN ";
                $sql .= $join->source->toSql();
                $sql .= " ON " . $join->on->toSql() . "\n";
            }
        }

        if (!empty($this->where)) {
            $whereClause = $this->reduceWhere($this->where)->toSql();
            $sql .= " WHERE " . $whereClause;
        }

        if (!empty($this->groupBy)) {
            $groupByClause = implode(",\n", array_map(fn(SqlExpression $expr) => $expr->toSql(), $this->groupBy));
            $sql .= " GROUP BY " . $groupByClause;
        }

        if (!empty($this->orderBy)) {
            $orderByClause = implode(",\n", array_map(fn(SqlExpression $expr) => $expr->toSql(), $this->orderBy));
            $sql .= " ORDER BY " . $orderByClause;
        }

        return trim($sql);
    }


}
