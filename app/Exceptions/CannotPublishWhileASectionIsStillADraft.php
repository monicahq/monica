<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to publish a template version that still carries a
 * section nobody has published.
 *
 * Freezing a version is only meaningful if what it contains is frozen too, all
 * the way down to the fields. A version pointing at a section somebody is still
 * editing would keep changing under the records on it, which is the one thing
 * publication promises will not happen. Publish the section first, deliberately.
 */
class CannotPublishWhileASectionIsStillADraft extends RuntimeException {}
