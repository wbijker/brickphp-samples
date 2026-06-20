<?php

namespace App;

/**
 * Per-app configuration passed into Brick::run / Brick::watch as the
 * second argument. The framework reads these fields via the injected
 * instance — no static state, no library-owned defaults.
 */
class Config extends \BrickPHP\Config
{

    public function __construct()
    {
        $this->development = true;
        $this->editorUrl = 'phpstorm://open?file={file}&line={line}';
        $this->editorHostRoot = '/Users/willembijker/projects/brickphp-samples';

    }

}
