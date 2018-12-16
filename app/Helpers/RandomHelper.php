<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class RandomHelper
{
    /**
     * Generate a new UUID to grab a new adorable avatar.
     *
     * @return string
     */
    public static function uuid()
    {
        return Str::uuid()->toString();
    }
}
