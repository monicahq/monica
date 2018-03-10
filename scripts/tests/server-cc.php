<?php

/**
 * Copy of server.php with Code Coverage capture.
 */

/** Coverage files destination. */
const STORAGE = '/results/coverage';

$root = realpath(__DIR__.'/../../');

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists($root.'/public'.$uri)) {
    return false;
}

/**
 * Set CodeCoverage via LiveCodeCoverage.
 */
require $root.'/vendor/autoload.php';

$shutDownCodeCoverage = \LiveCodeCoverage\LiveCodeCoverage::bootstrap(
    true,
    $root.STORAGE,
    $root.'/phpunit.xml'
);

require_once $root.'/public/index.php';

$shutDownCodeCoverage();
