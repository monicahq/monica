<?php

namespace App\Traits;

trait DAVFormat
{
    /**
     * Formats and returns a string for DAV Card/Cal.
     */
    private function formatValue(?string $value): ?string
    {
        return ! empty($value) ? str_replace('\;', ';', trim($value)) : null;
    }
}
