<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

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

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

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
     * The colour alone, for an icon drawn on the page rather than on a tint: the text half of tint().
     */
    public function iconForeground(): string
    {
        return match ($this) {
            self::Blue => 'text-[#3f5fd0] dark:text-[#93a7f0]',
            self::Green => 'text-[#3f7239] dark:text-[#5faa6e]',
            self::Amber => 'text-[#7f571d] dark:text-[#d9a441]',
            self::Rust => 'text-[#8a3826] dark:text-[#dd7f66]',
            self::Violet => 'text-[#5b4192] dark:text-[#b49ae4]',
            self::Teal => 'text-[#26686e] dark:text-[#5fb3bb]',
            self::Plum => 'text-[#8e3866] dark:text-[#e08ab8]',
            self::Slate => 'text-[#4a4a52] dark:text-[#a9a9b3]',
        };
    }

    /** @return array{light: string, dark: string, hue: string, alpha: float} */
    public function measurable(): array
    {
        return [
            'light' => $this->foreground(),
            'dark' => $this->liftedForeground(),
            'hue' => $this->value,
            'alpha' => 0.12,
        ];
    }

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

    public static function neutralTint(): string
    {
        return 'border-hairline bg-card text-body';
    }

    public static function tintFor(?string $colour): string
    {
        return self::tryFrom((string) $colour)?->tint() ?? self::neutralTint();
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
