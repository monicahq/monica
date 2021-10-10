<?php

namespace App\Helpers;

class StringHelper
{
    /**
     * Test if string is null or whitespace.
     *
     * @param  mixed  $text
     * @return bool
     */
    public static function isNullOrWhitespace($text): bool
    {
        return ctype_space($text) || $text === '' || is_null($text);
    }
}
