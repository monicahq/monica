<?php

/**
 * Copy of server.php with Code Coverage capture
 */

/**
 * Coverage files destination
 */
const STORAGE = '/results/coverage';

require __DIR__ . '/vendor/autoload.php';

use LiveCodeCoverage\LiveCodeCoverage;

LiveCodeCoverage::bootstrap(
    __DIR__ . STORAGE,
    __DIR__ . '/phpunit.xml'
);

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
