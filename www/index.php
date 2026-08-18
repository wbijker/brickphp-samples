<?php

use App\Config;
use BrickPHP\Brick;
use Samples\FlagQuiz\FlagQuizApp;

require 'vendor/autoload.php';

// Static-file pass-through for PHP's built-in dev server (`php -S`).
// Apache (the production server) handles statics natively, but the cli
// server pipes every request through this router unless we explicitly
// return false for paths that already point to a real file on disk.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

// Styling: /style.css (the preflight reset) plus the app's utility rules are
// handled by Brick::run — the utilities are collected from the actual render
// and emitted inline, then kept current across patches via the POST response.
// No static source scan.
Brick::run(FlagQuizApp::class, new Config());
