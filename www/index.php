<?php

use App\Config;
use BrickPHP\Brick;
use BrickPHP\UI\CssExtractor;
use Samples\Docs\DocsApp;

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

// /style.css — preflight reset stitched together with the utility rules
// extracted by lex-scanning the News sample's PHP source. Preflight goes
// first so app rules can override its defaults. Versioned via ?h=<styleVersion>
// in the <link> tag; the browser keys its cache off the full URL so a fresh
// hash forces a refetch.
if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/style.css')) {
    header('Content-Type: text/css; charset=utf-8');
    header('Cache-Control: public, max-age=31536000, immutable');
    echo "/* ==========================================================================\n";
    echo "   PREFLIGHT — browser reset (from preflight.css)\n";
    echo "   ========================================================================== */\n\n";
    echo file_get_contents(Brick::assetPath('preflight.css'));
    echo "\n/* ==========================================================================\n";
    echo "   APPLICATION — utility rules extracted from samples/News\n";
    echo "   ========================================================================== */\n\n";
    echo (new CssExtractor())->scan(__DIR__ . '/../samples/Docs');
    exit;
}

Brick::run(DocsApp::class, new Config());
