<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when something tries to rewrite the state a snapshot captured. A
 * snapshot exists so that somebody can go back to a moment, and a moment that
 * can be edited afterwards is not one.
 *
 * The one thing that does move is the note of when the snapshot was used to
 * restore, which is about the snapshot rather than about the state in it.
 */
class SnapshotIsImmutable extends RuntimeException {}
