<?php

namespace App\Domains\Contact\ManageContactImportantDates\Web\ViewHelpers;

use App\Helpers\ImportantDateHelper;
use App\Models\Contact;
use App\Models\User;

class ModuleImportantDatesViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $dates = $contact->importantDates;

        $datesCollection = $dates->map(function ($date) use ($user) {
            return [
                'id' => $date->id,
                'label' => $date->label,
                'date' => ImportantDateHelper::formatDate($date, $user),
                'type' => $date->contactImportantDateType ? $date->contactImportantDateType->label : null,
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
