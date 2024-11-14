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
     * If the user has an avatar_style value set from his settings page the Dicebear
     * avatar library will be used, otherwise it defaults to Multiavatar.
     *
     * The Multiavatar and Dicebear libraries take a name to generate a unique avatar.
     * However, contacts can be created in Monica without a name. When this case
     * happens, we'll generate a fake name for the contact, and generate an avatar
     * based on that name.
     */
    public static function generateRandomAvatar(Contact $contact, $avatarStyle = null): array
    {
        if (is_null($contact->first_name)) {
            $name = Faker::create()->name();
        } else {
            $name = $contact->first_name.' '.$contact->last_name;
        }

        if ($avatarStyle) {
            return [
                'type' => Contact::AVATAR_TYPE_URL,
                'content' => self::getDicebearLink($avatarStyle, $name),
            ];
        } else {
            $multiavatar = new MultiAvatar;

            return [
                'type' => Contact::AVATAR_TYPE_SVG,
                'content' => $multiavatar($name, null, null),
            ];
        }
    }

    public static function getDicebearLink($avatarStyle, $name)
    {
        return "https://api.dicebear.com/9.x/{$avatarStyle}/svg?seed={$name}";
    }
}
