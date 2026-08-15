<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Carbon\Carbon;

/**
 * How long a share link keeps working, as the four answers the screen offers.
 *
 * A link stores a date rather than a choice, so this enum only exists on the way
 * in: the form offers these, and the controller turns the one that was picked
 * into the date the action writes. Reading a stored date back as one of these
 * would be a guess, which is why the edit form keeps the date it already has
 * instead of trying.
 *
 * `Never` is offered because somebody sharing with a family member has no
 * sensible date to give, and refusing them one would push them to pick three
 * months and forget. It is the one case the screen says something about.
 */
enum ShareLinkExpiryEnum: string implements Translatable
{
    case SevenDays = 'seven_days';
    case ThirtyDays = 'thirty_days';
    case ThreeMonths = 'three_months';
    case Never = 'never';

    public function label(): string
    {
        return match ($this) {
            self::SevenDays => 'In 7 days',
            self::ThirtyDays => 'In 30 days',
            self::ThreeMonths => 'In 3 months',
            self::Never => 'Never',
        };
    }

    /**
     * The date this case means, counted from now, or null for the link that
     * never stops on its own.
     */
    public function expiresAt(): ?Carbon
    {
        return match ($this) {
            self::SevenDays => now()->addDays(7),
            self::ThirtyDays => now()->addDays(30),
            self::ThreeMonths => now()->addMonths(3),
            self::Never => null,
        };
    }

    /**
     * What the screen says under the select, which is a different sentence for
     * the link nobody will remember to revoke.
     */
    public function help(): string
    {
        return match ($this) {
            self::Never => 'A link with no end date is one you have to remember to revoke.',
            default => 'After that the address stops working on its own.',
        };
    }

    /**
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->label(), $this->help()];
    }
}
