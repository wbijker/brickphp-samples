<?php

namespace CodeQuery\Queryable;

use CodeQuery\Columns\Column;
use CodeQuery\Columns\IntColumn;
use CodeQuery\Expressions\Frame;
use CodeQuery\Expressions\FunctionExpression;
use CodeQuery\Expressions\OverExpression;
use CodeQuery\Expressions\StarExpression;

class WindowFunction
{
    /*
     * $windows = WindowFunctions::rowNumber()
        ->partitionBy($p->id)
        ->orderBy($p->price);

    1.	PARTITION BY – splits rows into groups (like GROUP BY, but rows aren’t collapsed).
	2.	ORDER BY – defines ordering of rows inside each partition.
	3.	FRAME clause – defines the “window frame” (which subset of rows around the current row is visible to the function).

    */
    static function rowNumber(?Column $orderBy = null, ?Column $partitionBy = null, ?Frame $frame = null): IntColumn
    {
        /*
    -- ROWS: count physical rows
    SUM(amount) OVER (
    ORDER BY day
    ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING
    ) AS sum_rows_1p1f,

    Usage: WindowFunction::rowNumber(orderBy: $p->day, frame: Frame::rows(Frame::value(1), Frame::value(1)));

     */

        return new IntColumn(new OverExpression(
            base: new FunctionExpression("ROW_NUMBER", []),
            orderBy: $orderBy ? Query::toExpression($orderBy) : null,
            partitionBy: $partitionBy ? Query::toExpression($partitionBy) : null,
            frame: $frame));
    }


}

