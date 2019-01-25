<?php

namespace App\Helpers;

use App\Models\Contact\Contact;

class AvatarHelper
{
    /**
     * Get an avatar.
     *
     * @return string
     */
    public static function get(Contact $contact, $size)
    {
        $htmlString = '<div class="absolute profile-page-avatar">';
        if ($contact->has_avatar) {
            $htmlString .= '<img src="'.$contact->getAvatarURL(110).'" class="h3 w3 dib tc" width="'.$size.'"></div>';
        } else {
            if (! is_null($contact->gravatar_url)) {
                $htmlString .= '<img src="'.$contact->gravatar_url.'" class="h3 w3 dib tc" width="'.$size.'"></div>';
            } else {
                if (strlen($contact->getInitials()) == 1) {
                    $htmlString .= '<div class="h3 w3 dib pt3 white tc f4" style="background-color: '.$contact->getAvatarColor().'">'.$contact->getInitials().'</div></div>';
                } else {
                    $htmlString .= '<div class="h3 w3 dib pt3 white tc f4" style="background-color: '.$contact->getAvatarColor().'">'.$contact->getInitials().'</div></div>';
                }
            }
        }

        return $htmlString;
    }
}
