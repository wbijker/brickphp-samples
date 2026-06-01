<?php

use BrickPHP\Brick;
use Samples\Docs\DocsApp;

require 'vendor/autoload.php';

// Identical to index.php — Brick::run sees the `/hmr.php` URL and
// internally dispatches to the HMR long-poll endpoint. Kept as a real
// file so Apache can serve the request without needing a rewrite rule.
Brick::run(DocsApp::class);
