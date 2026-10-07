<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use Illuminate\Support\Facades\File;

/**
 * The languages the instance is translated into, which is what the lang folder
 * holds rather than what the configuration lists: a locale is available because
 * `lang/{locale}.json` is there, which `php artisan lang:add` writes. The
 * `locales` array of `config/localization.php` only describes it, and a locale
 * it says nothing about is still offered, under its own code.
 *
 * The source language is always available, since `php artisan monica:localize`
 * writes its file from the code.
 *
 * The folder is read once per request: a screen asks for the list several
 * times, and the files only change with a deployment.
 */
class AvailableLocales
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        $locales = [];

        foreach (self::codes() as $code) {
            $locales[$code] = self::details($code);
        }

        return $locales;
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return once(function (): array {
            $codes = array_map(
                fn (string $path): string => basename($path, '.json'),
                File::glob(lang_path('*.json')) ?: [],
            );

            $codes[] = (string) config('app.locale');

            $codes = array_unique($codes);

            sort($codes);

            return $codes;
        });
    }

    public static function has(string $locale): bool
    {
        return in_array($locale, self::codes(), true);
    }

    /** @return array<string, mixed> */
    public static function details(string $locale): array
    {
        $defaults = [
            'label' => $locale,
            'region' => '',
            'flag' => '',
            'date_format' => DateFormatEnum::DayFirst->value,
            'number_format' => NumberFormatEnum::CommaAndDot->value,
        ];

        return array_merge($defaults, (array) config("localization.locales.{$locale}", []));
    }
}
