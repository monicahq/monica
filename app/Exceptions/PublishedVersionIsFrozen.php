<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when something tries to change a version that has been published, or
 * anything inside it.
 *
 * This is the rule the whole personalisation engine rests on. Records point at
 * a precise version and keep following it until somebody deliberately moves
 * them, and that promise is only worth something if a published version cannot
 * change under them. So the application refuses the write rather than trusting
 * every screen and every action to remember. The way forward is always the
 * same: make a draft from it, change the draft, publish it beside the old one.
 */
class PublishedVersionIsFrozen extends RuntimeException {}
