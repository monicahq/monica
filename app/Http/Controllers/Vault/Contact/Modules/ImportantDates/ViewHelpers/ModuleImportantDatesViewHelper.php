<?php

namespace App\Http\Controllers\Vault\Contact\Modules\ImportantDates\ViewHelpers;

use App\Models\Contact;
use App\Helpers\AgeHelper;

class ModuleImportantDatesViewHelper
{
    public static function data(Contact $contact): array
    {
        $dates = $contact->dates;

        $datesCollection = $dates->map(function ($date) {
            return [
                'id' => $date->id,
                'label' => $date->label,
                'date' => $date->date,
                'type' => $date->type,
                'age' => AgeHelper::getAge($date->date),
            ];
        });

        return [
            'dates' => $datesCollection,
            'url' => [
                'edit' => route('contact.edit', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}
