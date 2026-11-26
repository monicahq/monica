<?php

declare(strict_types=1);

namespace App\Helpers;

use Stevebauman\Purify\Facades\Purify;

class TextSanitizer
{
    public static function plainText(string $value): string
    {
        $stripped = Purify::config(['HTML.Allowed' => ''])->clean(str_replace('&', '&amp;', $value));

        return mb_trim(html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
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

        $stripped = strip_tags(self::html($value));

        $sanitized = mb_trim(html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $sanitized === '' ? null : $sanitized;
    }
}
