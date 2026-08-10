<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody gives a page the label another page of the same template
 * version already carries.
 *
 * The label is what the tab says, and it is the only thing that tells one tab
 * from another. Two tabs reading the same leave somebody navigating by name,
 * whether by eye or by screen reader, with no way to say which is which.
 */
class PageLabelIsAlreadyUsed extends RuntimeException {}
