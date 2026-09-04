<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the way somebody wants to be told things cannot be read.
 *
 * Quiet hours with only one end, a summary with no hour to be sent at, and a
 * zone the product does not know are all settings a delivery would have to
 * invent the missing half of. Refusing is what keeps it from guessing.
 */
class NotificationPreferenceIsNotValid extends RuntimeException {}
