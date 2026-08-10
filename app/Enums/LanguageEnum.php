<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;

/**
 * The languages somebody is most likely to note a contact speaks. Ten to start
 * from, added to by whoever needs an eleventh.
 *
 * The key is the ISO 639-1 code. It has nothing to do with the languages the
 * interface itself is translated into: this is what a contact speaks, not what
 * the person reading the screen does.
 */
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

    /**
     * The name of the language, which doubles as the translation key.
     */
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

    /**
     * The name this item is seeded with.
     *
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
