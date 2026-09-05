<?php

declare(strict_types=1);

namespace App\Helpers;

use Stevebauman\Purify\Facades\Purify;

class TextSanitizer
{
    public static function plainText(string $value): string
    {
        $stripped = Purify::config(['HTML.Allowed' => ''])->clean($value);

        return mb_trim($stripped);
    }

    public static function nullablePlainText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = self::plainText($value);

        return $sanitized === '' ? null : $sanitized;
    }

    public static function html(string $value): string
    {
        return Purify::clean($value);
    }

    public static function nullableHtml(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = mb_trim(strip_tags(self::html($value)));

        return $sanitized === '' ? null : $sanitized;
    }
}
