<?php

use Samples\News\NewsApp;
use BrickPHP\Brick;

require 'vendor/autoload.php';

// Dev-only HMR endpoint. Long-polls for a source change and publishes the
// current fingerprint into BrickPHP\Hash; the client reloads when it reports
// changed. Config (incl. the dev gate) comes from the app.
Brick::watch(NewsApp::class);
