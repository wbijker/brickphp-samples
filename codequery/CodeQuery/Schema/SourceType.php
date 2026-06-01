<?php

namespace CodeQuery\Schema;

use CodeQuery\Sources\SqlSource;

class SourceType
{
    function __construct(public string    $type,
                         public SqlSource $source,
                         public object    $instance,
    )
    {
    }
}