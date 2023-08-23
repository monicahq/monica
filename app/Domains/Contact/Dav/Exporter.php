<?php

namespace App\Domains\Contact\Dav;

abstract class Exporter
{
    protected function escape(mixed $value): ?string
    {
        $value = (string) $value;

        return ! empty($value) ? trim($value) : null;
    }

    /**
     * Formats and returns a string for DAV Card/Cal.
     */
    protected function formatValue(?string $value): ?string
    {
        return ! empty($value) ? str_replace('\;', ';', trim($value)) : null;
    }
}
