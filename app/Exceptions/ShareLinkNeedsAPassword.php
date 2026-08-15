<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a link protected by a password is opened without one. It is the
 * normal first step rather than a failure, so nothing is written down and the
 * screen simply asks.
 */
class ShareLinkNeedsAPassword extends RuntimeException {}
