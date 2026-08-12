<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody submits a listing that already has a version waiting for
 * a decision. Two versions in the queue at once would leave whoever moderates
 * them deciding about a state the author has already moved on from.
 */
class ListingIsAlreadyAwaitingModeration extends RuntimeException {}
