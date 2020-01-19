<?php

namespace App\Helpers;

use App\Models\Contact\Contact;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AvatarHelper
{
    /**
     * Returns the URL of the avatar for the given contact, properly sized.
     * The avatar can come from 4 sources:
     *  - default,
     *  - Adorable avatar,
     *  - Gravatar
     *  - or a photo that has been uploaded.
     */
    public static function getAvatarURL(Contact $contact): string
    {
        $avatarURL = '';

        switch ($contact->avatar_source) {
            case 'adorable':
                $avatarURL = $contact->avatar_adorable_url;
                break;
            case 'gravatar':
                $avatarURL = $contact->avatar_gravatar_url;
                break;
            case 'photo':
                $avatarURL = $contact->avatarPhoto()->get()->first()->url();
                break;
            case 'default':
                $avatarURL = $contact->getAvatarDefaultURL();
                break;
        }

        return $avatarURL;
    }

    /**
     * Get the default avatar URL for the given contact.
     */
    public static function getAvatarDefaultURL(Contact $contact): string
    {
        if (empty($contact->avatar_default_url)) {
            return '';
        }

        try {
            $matches = preg_split('/\?/', $contact->avatar_default_url);
            $url = asset(Storage::disk(config('filesystems.default'))->url($matches[0]));
            if (count($matches) > 1) {
                $url .= '?' . $matches[1];
            }

            return $url;
        } catch (\Exception $e) {
            return '';
        }
    }
}
