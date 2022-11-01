<?php

namespace App\Domains\Contact\ManageAvatar\Web\ViewHelpers;

use App\Models\Contact;

class ModuleAvatarViewHelper
{
    public static function data(Contact $contact): array
    {
        return [
            'avatar' => $contact->avatar,
        ];
    }
}
