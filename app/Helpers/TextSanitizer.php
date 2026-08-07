<?php

declare(strict_types=1);

namespace App\Helpers;

use Stevebauman\Purify\Facades\Purify;

/**
 * Provides utility methods for cleaning user input.
 *
 * This class removes potentially dangerous HTML/PHP tags from user-submitted
 * text to prevent XSS (cross-site scripting) attacks.
 */
class TextSanitizer
{
    /**
     * Remove all HTML and return plain text only.
     * Uses HTMLPurifier with no allowed tags instead of strip_tags.
     */
    public static function plainText(string $value): string
    {
        $stripped = Purify::config(['HTML.Allowed' => ''])->clean($value);

        return mb_trim($stripped);
    }

    /**
     * Plain text sanitization that returns null for empty results.
     */
    public static function nullablePlainText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = self::plainText($value);

        return $sanitized === '' ? null : $sanitized;
    }

    /**
     * Sanitize rich HTML, preserving safe formatting tags.
     */
    public static function html(string $value): string
    {
        return Purify::clean($value);
    }

    /**
     * Rich HTML sanitization that returns null for empty results.
     */
    public static function nullableHtml(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = mb_trim(strip_tags(self::html($value)));

        return $sanitized === '' ? null : $sanitized;
    }
}
