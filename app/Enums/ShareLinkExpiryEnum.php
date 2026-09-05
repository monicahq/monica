<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Carbon\CarbonImmutable;
use Override;

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

    public function expiresAt(): ?CarbonImmutable
    {
        return match ($this) {
            self::SevenDays => now()->addDays(7),
            self::ThirtyDays => now()->addDays(30),
            self::ThreeMonths => now()->addMonths(3),
            self::Never => null,
        };
    }

    public function help(): string
    {
        return match ($this) {
            self::Never => 'A link with no end date is one you have to remember to revoke.',
            default => 'After that the address stops working on its own.',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->help()];
    }
}
