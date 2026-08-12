<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a record is created for a kind of object that is no longer
 * offered. Deactivating a type stops new records being created and leaves the
 * existing ones readable and editable, which is the whole difference between
 * deactivating it and deleting it.
 */
class EntityTypeIsNotActive extends RuntimeException {}
