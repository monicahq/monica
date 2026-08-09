<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to delete the contact representing themselves.
 * Relationships and activities are written from that contact's point of view,
 * so deleting it would leave the vault talking about nobody.
 */
class CannotDeleteTheMeContact extends RuntimeException {}
