<?php

namespace App\Domains\Contact\ManageAvatar\Web\ViewHelpers;

use App\Helpers\StorageHelper;
use App\Models\Contact;

class ModuleAvatarViewHelper
{
    public static function data(Contact $contact): array
    {
        return [
            'avatar' => $contact->avatar,
            'uploadcare' => StorageHelper::uploadcare(),
            'url' => [
                'update' => route('contact.avatar.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'destroy' => route('contact.avatar.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'suggest' => route('contact.avatar.suggest', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}
