<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class AvatarHelper
{
    /**
     * Generate a new UUID to grab a new adorable avatar.
     *
     * @return string
     */
    public static function generateAdorableUUID()
    {
        return (string) Str::uuid();
    }
}
