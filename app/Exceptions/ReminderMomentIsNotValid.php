<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the moments a reminder is asked for say nothing usable.
 *
 * A set with no moment in it, two moments that are the same, a moment before a
 * date with no amount, and one reaching further than its unit allows are all
 * refused rather than tidied, because filling in what somebody meant is a guess.
 *
 * It carries the sentence the rule produced, so the screen shows the reader why
 * rather than a generic refusal.
 */
class ReminderMomentIsNotValid extends RuntimeException {}
