<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

/**
 * The colours a vault may be told apart by.
 *
 * A short list rather than a free colour, so that a switcher holding half a
 * dozen vaults stays readable and every one of them reads against both themes.
 * The colour carries no meaning of its own: it is there to be recognised at a
 * glance, which is why each has a name somebody can hear read out.
 *
 * The backing value is the `colour` column of the vault.
 */
enum VaultColourEnum: string implements Translatable
{
    case Blue = '#3f5fd0';
    case Green = '#4f8a49';
    case Amber = '#9a6a24';
    case Rust = '#9a3f2b';
    case Violet = '#6b4fa8';
    case Teal = '#2f7f86';
    case Plum = '#a8447a';
    case Slate = '#4a4a52';

    /**
     * What the colour is called, which is what somebody using a screen reader
     * hears instead of a hex code. The name doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::Blue => 'Blue',
            self::Green => 'Green',
            self::Amber => 'Amber',
            self::Rust => 'Rust',
            self::Violet => 'Violet',
            self::Teal => 'Teal',
            self::Plum => 'Plum',
            self::Slate => 'Slate',
        };
    }

    /**
     * Every colour on offer, as the hex codes the column holds.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The name of this colour, which the picker reads.
     *
     * @return list<string>
     */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
