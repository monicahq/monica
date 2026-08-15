<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody edits or revokes a link that was already revoked.
 * Revocation is immediate and permanent, and a revoked link is never brought
 * back: a new one is made instead.
 */
class ShareLinkIsRevoked extends RuntimeException {}
