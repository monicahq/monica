<?php

namespace App\Domains\Contact\ManageContactImportantDates\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Helpers\ImportantDateHelper;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\User;

class ContactImportantDatesViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $dates = $contact->importantDates;

        $datesCollection = $dates->map(function ($date) use ($user, $contact) {
            return self::dto($contact, $date, $user);
        });

        $dateTypesCollection = $contact->vault->contactImportantDateTypes()
            ->get()
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->label,
                ];
            });
        // we add an empty field so the dropdown field can be nullified
        $dateTypesCollection->prepend([
            'id' => -1,
            'name' => '',
        ]);

        return [
            'contact' => [
                'name' => $contact->name,
            ],
            'dates' => $datesCollection,
            'months' => DateHelper::getMonths(),
            'days' => DateHelper::getDays(),
            'date_types' => $dateTypesCollection,
            'url' => [
                'store' => route('contact.date.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dto(Contact $contact, ContactImportantDate $date, User $user): array
    {
        $completeDate = '';
        if (ImportantDateHelper::determineType($date) == ContactImportantDate::TYPE_FULL_DATE) {
            $completeDate = $date->year.'-'.$date->month.'-'.$date->day;
        }

        return [
            'id' => $date->id,
            'label' => $date->label,
            'date' => ImportantDateHelper::formatDate($date, $user),
            'type' => $date->contactImportantDateType ? [
                'id' => $date->contactImportantDateType->id,
                'label' => $date->contactImportantDateType->label,
            ] : null,
            'age' => ImportantDateHelper::getAge($date),
            'choice' => ImportantDateHelper::determineType($date),
            'completeDate' => $completeDate,
            'month' => $date->month,
            'day' => $date->day,
            'url' => [
                'update' => route('contact.date.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'date' => $date->id,
                ]),
                'destroy' => route('contact.date.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'date' => $date->id,
                ]),
            ],
        ];
    }
}
