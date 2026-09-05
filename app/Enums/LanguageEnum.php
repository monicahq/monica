<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

enum LanguageEnum: string implements SuppliedValueListItem
{
    case English = 'en';
    case French = 'fr';
    case German = 'de';
    case Spanish = 'es';
    case Portuguese = 'pt';
    case Dutch = 'nl';
    case Italian = 'it';
    case Mandarin = 'zh';
    case Japanese = 'ja';
    case Arabic = 'ar';

    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::French => 'French',
            self::German => 'German',
            self::Spanish => 'Spanish',
            self::Portuguese => 'Portuguese',
            self::Dutch => 'Dutch',
            self::Italian => 'Italian',
            self::Mandarin => 'Mandarin Chinese',
            self::Japanese => 'Japanese',
            self::Arabic => 'Arabic',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
