<?php

namespace App\Http\Controllers\Vault\Contact\ImportantDates\ViewHelpers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Contact;
use App\Helpers\AgeHelper;
use App\Helpers\DateHelper;
use App\Models\ContactDate;
use Illuminate\Support\Str;

class ContactImportantDatesViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $dates = $contact->dates;

        $datesCollection = $dates->map(function ($date) use ($user, $contact) {
            return self::dto($contact, $date, $user);
        });

        return [
            'contact' => [
                'name' => $contact->getName($user),
            ],
            'dates' => $datesCollection,
            'months' => DateHelper::getMonths(),
            'days' => DateHelper::getDays(),
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

    public static function dto(Contact $contact, ContactDate $date, User $user): array
    {
        $choice = '';
        $completeDate = '';
        $month = '';
        $day = '';

        switch (strlen($date->date)) {
            case 10:
                // case: full date
                $choice = 'exactDate';
                $completeDate = Carbon::parse($date->date)->format('Y-m-d');
                break;

            case 5:
                // case: only know the month and day.
                $choice = 'monthDay';
                $month = (int) ltrim(Str::substr($date->date, 0, 2), '0');
                $day = (int) ltrim(Str::substr($date->date, 3, 2), '0');
                break;

            case 4:
                // case: only know the age.
                $choice = 'age';
                $age = Carbon::createFromFormat('Y', $date->date)->age;
                break;

            default:
                $completeDate = $date->date;
                break;
        }

        return [
            'id' => $date->id,
            'label' => $date->label,
            'date' => AgeHelper::formatDate($date->date, $user),
            'type' => $date->type,
            'age' => AgeHelper::getAge($date->date),
            'choice' => $choice,
            'completeDate' => $completeDate,
            'month' => $month,
            'day' => $day,
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
