<?php

namespace App\Http\Controllers\Vault\Contact\Modules\ImportantDates\ViewHelpers;

use App\Models\User;
use App\Models\Contact;
use App\Helpers\AgeHelper;

class ModuleImportantDatesViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $dates = $contact->dates;

        $datesCollection = $dates->map(function ($date) use ($user) {
            return [
                'id' => $date->id,
                'label' => $date->label,
                'date' => AgeHelper::formatDate($date->date, $user),
                'type' => $date->type,
                'age' => AgeHelper::getAge($date->date),
            ];
        });

        return [
            'dates' => $datesCollection,
            'url' => [
                'edit' => route('contact.date.index', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}
