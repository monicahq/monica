<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum NumberFormatEnum: string implements Translatable
{
    case CommaAndDot = 'comma_dot';
    case DotAndComma = 'dot_comma';
    case SpaceAndComma = 'space_comma';
    case NoSeparator = 'no_separator';

    public function label(): string
    {
        return match ($this) {
            self::CommaAndDot => 'Comma and dot',
            self::DotAndComma => 'Dot and comma',
            self::SpaceAndComma => 'Space and comma',
            self::NoSeparator => 'No separator',
        };
    }

    public function example(): string
    {
        return $this->format(1234.56);
    }

    public function format(float|int $number, int $decimals = 2): string
    {
        return number_format($number, $decimals, $this->decimalSeparator(), $this->thousandsSeparator());
    }

    public function thousandsSeparator(): string
    {
        return match ($this) {
            self::CommaAndDot => ',',
            self::DotAndComma => '.',
            self::SpaceAndComma => "\u{00A0}",
            self::NoSeparator => '',
        };
    }

    public function decimalSeparator(): string
    {
        return match ($this) {
            self::CommaAndDot, self::NoSeparator => '.',
            self::DotAndComma, self::SpaceAndComma => ',',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
