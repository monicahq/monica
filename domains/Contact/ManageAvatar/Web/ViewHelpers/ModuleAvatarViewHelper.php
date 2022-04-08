<?php

namespace App\Contact\ManageAvatar\Web\ViewHelpers;

use App\Models\Contact;

class ModuleAvatarViewHelper
{
    public static function data(Contact $contact): string
    {
        return '<img class="h-20 w-20 mx-auto rounded-full" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">';
    }
}
