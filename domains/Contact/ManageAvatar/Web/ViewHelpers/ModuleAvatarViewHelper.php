<?php

namespace App\Contact\ManageAvatar\Web\ViewHelpers;

use App\Helpers\AvatarHelper;
use App\Models\Contact;

class ModuleAvatarViewHelper
{
    public static function data(Contact $contact): string
    {
        return AvatarHelper::getSVG($contact);
    }
}
