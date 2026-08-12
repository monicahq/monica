<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to offer a structure nobody has published yet.
 * What travels to the catalogue is a frozen version, so a template or a section
 * that only exists as a draft has nothing to send.
 */
class ListingHasNothingToPublish extends RuntimeException {}
