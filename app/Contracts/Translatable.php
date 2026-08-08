<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * An enum whose cases carry words that end up on a screen.
 *
 * Those words are written in the enum but translated where they are read, so
 * nothing in the enum itself looks like a string anybody has to translate. This
 * is how an enum says that it does, and how `monica:localize` finds them.
 */
interface Translatable
{
    /**
     * Every string of this case that has to reach the locale files.
     *
     * @return list<string>
     */
    public function translationKeys(): array;
}
