<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
