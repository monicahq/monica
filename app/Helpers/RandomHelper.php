<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class RandomHelper
{
    /**
     * Generate a UUID.
     *
     * @return string
     */
    public static function uuid()
    {
        return Str::uuid()->toString();
    }
}
