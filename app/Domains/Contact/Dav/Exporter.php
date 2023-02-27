<?php

namespace App\Domains\Contact\Dav;

abstract class Exporter
{
    protected function escape(mixed $value): ?string
    {
        $value = (string) $value;

        return ! empty($value) ? trim($value) : null;
    }
}
