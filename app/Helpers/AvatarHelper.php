<?php

namespace App\Helpers;

use App\Models\Contact\Contact;

class AvatarHelper
{
    /**
     * Generate a new UUID to grab a new adorable avatar.
     *
     * @return string
     */
    public static function generateAdorableUUID()
    {
        return str_random(32);
    }
}
