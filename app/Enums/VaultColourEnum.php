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
     * The utilities that draw something in this colour: the hue at a twelfth of
     * itself behind, and a foreground picked per theme.
     *
     * The foreground is not the hue. A hue chosen to read as a vault's mark on
     * paper is too dark on a dark ground, so each is darkened for the light
     * theme and lifted for the dark one. Skipping that step measures 2.15:1 for
     * Slate, which is why the pair is a table rather than a formula.
     *
     * The ground is an alpha rather than a mix with a named colour, so it
     * composites over whatever is behind it and needs no second value per theme.
     *
     * Each pair names its own border colour. Two `border-*` utilities in one
     * class attribute are not resolved by their order in that attribute but by
     * their order in the generated stylesheet, so a caller cannot lay a default
     * border down and count on the tint to cover it.
     */
    public function tint(): string
    {
        return match ($this) {
            self::Blue => 'border-transparent bg-[#3f5fd0]/12 text-[#3f5fd0] dark:text-[#93a7f0]',
            self::Green => 'border-transparent bg-[#4f8a49]/12 text-[#3f7239] dark:text-[#5faa6e]',
            self::Amber => 'border-transparent bg-[#9a6a24]/12 text-[#7f571d] dark:text-[#d9a441]',
            self::Rust => 'border-transparent bg-[#9a3f2b]/12 text-[#8a3826] dark:text-[#dd7f66]',
            self::Violet => 'border-transparent bg-[#6b4fa8]/12 text-[#5b4192] dark:text-[#b49ae4]',
            self::Teal => 'border-transparent bg-[#2f7f86]/12 text-[#26686e] dark:text-[#5fb3bb]',
            self::Plum => 'border-transparent bg-[#a8447a]/12 text-[#8e3866] dark:text-[#e08ab8]',
            self::Slate => 'border-transparent bg-[#4a4a52]/12 text-[#4a4a52] dark:text-[#a9a9b3]',
        };
    }

    /**
     * The same pair as raw hexes, which is what a contrast check needs and what
     * a Tailwind class string cannot give it. The background is the hue and the
     * proportion it is drawn at, since the ground it lands on decides the rest.
     *
     * @return array{light: string, dark: string, hue: string, alpha: float}
     */
    public function measurable(): array
    {
        return [
            'light' => $this->foreground(),
            'dark' => $this->liftedForeground(),
            'hue' => $this->value,
            'alpha' => 0.12,
        ];
    }

    /**
     * What the glyph is drawn in on a light ground.
     */
    public function foreground(): string
    {
        return match ($this) {
            self::Blue => '#3f5fd0',
            self::Green => '#3f7239',
            self::Amber => '#7f571d',
            self::Rust => '#8a3826',
            self::Violet => '#5b4192',
            self::Teal => '#26686e',
            self::Plum => '#8e3866',
            self::Slate => '#4a4a52',
        };
    }

    /**
     * What it is drawn in on a dark one.
     */
    public function liftedForeground(): string
    {
        return match ($this) {
            self::Blue => '#93a7f0',
            self::Green => '#5faa6e',
            self::Amber => '#d9a441',
            self::Rust => '#dd7f66',
            self::Violet => '#b49ae4',
            self::Teal => '#5fb3bb',
            self::Plum => '#e08ab8',
            self::Slate => '#a9a9b3',
        };
    }

    /**
     * The utilities for a mark carrying no colour, which is the default and the
     * ninth thing the picker offers.
     */
    public static function neutralTint(): string
    {
        return 'border-hairline bg-card text-body';
    }

    /**
     * The tint for a stored value, falling back to neutral for null and for
     * anything the set does not hold.
     */
    public static function tintFor(?string $colour): string
    {
        return self::tryFrom((string) $colour)?->tint() ?? self::neutralTint();
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
