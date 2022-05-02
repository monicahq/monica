<?php

namespace App\Contact\ManageAvatar\Web\ViewHelpers;

use App\Models\Contact;
use App\Helpers\AvatarHelper;

class ModuleAvatarViewHelper
{
    public static function data(Contact $contact): string
    {
        return AvatarHelper::getSVG($contact);
    }
}
