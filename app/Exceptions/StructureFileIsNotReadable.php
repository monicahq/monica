<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a file somebody pasted in cannot come in.
 *
 * The message is written to be shown. Every other refusal in the application is
 * about something the account already holds, and the person reading it knows
 * what they did; this one is about bytes that arrived from outside, possibly
 * written by somebody else, possibly a year ago, and the only useful thing to
 * say is which part of the file is wrong.
 *
 * A refusal means nothing was created. There is no half import.
 */
class StructureFileIsNotReadable extends RuntimeException {}
