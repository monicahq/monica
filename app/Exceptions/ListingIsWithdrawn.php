<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody works on a listing they have already pulled from the
 * catalogue. Withdrawing is the end of the road: what is wanted afterwards is a
 * new listing, not the quiet return of one people were told had gone.
 */
class ListingIsWithdrawn extends RuntimeException {}
