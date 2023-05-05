<?php

if (! function_exists('trans_key')) {
    /**
     * Extract the message.
     */
    function trans_key(?string $key = null): ?string
    {
        return $key;
    }
}

if (! function_exists('trans_ignore')) {
    /**
     * Translate the given message. It won't be extracted by monica:localize command.
     */
    function trans_ignore(?string $key = null, array $replace = [], ?string $locale = null): ?string
    {
        return __($key, $replace, $locale);
    }
}
