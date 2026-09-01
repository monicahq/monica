<?php

declare(strict_types=1);

namespace App\Enums;

use App\Actions\UpdateFieldValueRowPosition;
use App\Contracts\Translatable;
use Override;

/**
 * Which end of a repeater a new repetition is added at.
 *
 * A collection of documents grows at the end: a passport scanned first is not
 * the least important one, and reversing a set nobody asked to have reversed
 * would be a change made on their behalf. Notes are the other way round. Adding
 * one is how somebody records what they just learned, and reading them is how
 * they catch up on a person, so the newest belongs where they are already
 * looking rather than under everything they wrote in the years before.
 *
 * So it is a property of one repeater rather than a rule the product applies to
 * every repeater at once.
 *
 * It says where a new one lands and nothing else. Where an existing repetition
 * sits is {@see UpdateFieldValueRowPosition}, and the place a
 * repetition holds goes on meaning the order it appears in whichever direction
 * its repeater collects in, so first is the top for all of them. That is what
 * lets the two ideas be true at the same time.
 *
 * The backing value is the `repetition_order` column of the field.
 */
enum RepetitionOrderEnum: string implements Translatable
{
    case NewestLast = 'newest_last';
    case NewestFirst = 'newest_first';

    /**
     * What the direction is called where somebody chooses it. The sentence
     * doubles as the translation key, and it says what they will see rather
     * than which way anything is sorted.
     */
    public function label(): string
    {
        return match ($this) {
            self::NewestLast => 'New ones at the bottom',
            self::NewestFirst => 'New ones at the top',
        };
    }

    /**
     * A line under the name, since a direction is one of those choices somebody
     * understands instantly from an example and not at all from its name.
     */
    public function description(): string
    {
        return match ($this) {
            self::NewestLast => 'The list grows downwards, which suits a collection somebody builds up.',
            self::NewestFirst => 'The newest is first, which suits something read latest first.',
        };
    }

    /**
     * Whether a new repetition goes in front of the ones already there.
     */
    public function addsToTheFront(): bool
    {
        return $this === self::NewestFirst;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->description()];
    }
}
