<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody publishes a template version carrying a page with
 * nothing on it, while the version has other pages.
 *
 * A tab that opens on nothing is a dead end for whoever clicks it. A version
 * with a single empty page is another matter and publishes fine: a record whose
 * only content is the name imposed on it is still a record worth writing down.
 */
class CannotPublishAnEmptyPage extends RuntimeException {}
