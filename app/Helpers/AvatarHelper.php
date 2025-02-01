<?php

namespace App\Helpers;

use App\Models\Contact;
use App\Models\MultiAvatar;
use Faker\Factory as Faker;

class AvatarHelper
{
    /**
     * Generate a new random avatar.
     *
     * The Multiavatar library takes a name to generate a unique avatar.
     * However, contacts can be created in Monica without a name. When this case
     * happens, we'll generate a fake name for the contact, and generate an avatar
     * based on that name.
     */
    public static function generateRandomAvatar(Contact $contact): string
    {
        $multiavatar = new MultiAvatar;

        if (is_null($contact->first_name)) {
            $name = Faker::create()->name();
        } else {
            $name = $contact->first_name.' '.$contact->last_name;
        }

        return $multiavatar($name, null, null);
    }
}
