<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to delete a section one of whose versions sits in
 * a published template version.
 *
 * A published template version is frozen, and taking a section out from under it
 * would change what it renders, which is the one thing publication promises
 * will not happen. Deactivating the section stops it being added to anything
 * new and leaves the assemblies that already contain it alone.
 */
class CannotDeleteASectionInUse extends RuntimeException {}
