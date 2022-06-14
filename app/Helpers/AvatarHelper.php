<?php

namespace App\Helpers;

use App\Models\Avatar;
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
     *
     * @param  Contact  $contact
     * @return Avatar
     */
    public static function generateRandomAvatar(Contact $contact): Avatar
    {
        $multiavatar = new MultiAvatar();

        if (is_null($contact->first_name)) {
            $name = Faker::create()->name();
        } else {
            $name = $contact->first_name.' '.$contact->last_name;
        }

        $svgCode = $multiavatar($name, null, null);

        $avatar = Avatar::create([
            'contact_id' => $contact->id,
            'type' => Avatar::TYPE_GENERATED,
            'svg' => $svgCode,
        ]);

        return $avatar;
    }

    /**
     * Get the avatar of a contact.
     *
     * @param  Contact  $contact
     * @return string
     */
    public static function getSVG(Contact $contact): string
    {
        $avatar = $contact->currentAvatar;

        return $avatar->svg;
    }
}
