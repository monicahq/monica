<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the password given does not open the link. The attempt is written
 * down before this is raised, since a run of them is the only signal the owner
 * ever gets that a link may have leaked.
 */
class ShareLinkPasswordIsWrong extends RuntimeException {}
