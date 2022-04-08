<?php

namespace App\Contact\ManageContactImportantDates\Web\ViewHelpers;

use App\Models\User;
use App\Models\Contact;
use App\Helpers\ImportantDateHelper;

class ModuleImportantDatesViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $dates = $contact->dates;

        $datesCollection = $dates->map(function ($date) use ($user) {
            return [
                'id' => $date->id,
                'label' => $date->label,
                'date' => ImportantDateHelper::formatDate($date, $user),
                'type' => $date->type,
                'age' => ImportantDateHelper::getAge($date),
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
