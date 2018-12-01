<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


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
