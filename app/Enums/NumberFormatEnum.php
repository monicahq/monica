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

    /**
     * What the format is called on the screen where somebody chooses it. The
     * separators are named in the order they appear, thousands then decimal.
     * The sentence doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::CommaAndDot => 'Comma and dot',
            self::DotAndComma => 'Dot and comma',
            self::SpaceAndComma => 'Space and comma',
            self::NoSeparator => 'No separator',
        };
    }

    /**
     * The same number written this way, shown beside the name so nobody has to
     * picture what the separators do. It runs past a thousand and past the
     * decimal point on purpose, since that is where the formats differ.
     */
    public function example(): string
    {
        return $this->format(1234.56);
    }

    public function format(float|int $number, int $decimals = 2): string
    {
        return number_format($number, $decimals, $this->decimalSeparator(), $this->thousandsSeparator());
    }

    private function thousandsSeparator(): string
    {
        return match ($this) {
            self::CommaAndDot => ',',
            self::DotAndComma => '.',
            // A plain space would let a number break across two lines.
            self::SpaceAndComma => "\u{00A0}",
            self::NoSeparator => '',
        };
    }

    private function decimalSeparator(): string
    {
        return match ($this) {
            self::CommaAndDot, self::NoSeparator => '.',
            self::DotAndComma, self::SpaceAndComma => ',',
        };
    }

    /**
     * The name of this case, which the screen that offers the choice reads.
     *
     * @return list<string>
     */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
