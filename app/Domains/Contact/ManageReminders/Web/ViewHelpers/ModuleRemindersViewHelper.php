<?php

namespace App\Domains\Contact\ManageReminders\Web\ViewHelpers;

use App\Helpers\ContactReminderHelper;
use App\Helpers\DateHelper;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\User;

class ModuleRemindersViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $reminders = $contact->reminders()
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        $remindersCollection = $reminders->map(function ($reminder) use ($contact, $user) {
            return self::dtoReminder($contact, $reminder, $user);
        });

        return [
            'reminders' => $remindersCollection,
            'months' => DateHelper::getMonths(),
            'days' => DateHelper::getDays(),
            'url' => [
                'store' => route('contact.reminder.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dtoReminder(Contact $contact, ContactReminder $reminder, User $user): array
    {
        // determine the type
        $choice = 'full_date';
        if (! $reminder->year) {
            $choice = 'month_day';
        }

        return [
            'id' => $reminder->id,
            'label' => $reminder->label,
            'date' => ContactReminderHelper::formatDate($reminder, $user),
            'type' => $reminder->type,
            'frequency_number' => $reminder->frequency_number,
            'day' => $reminder->day,
            'month' => $reminder->month,
            'choice' => $choice,
            'reminder_choice' => $reminder->type == ContactReminder::TYPE_ONE_TIME ? ContactReminder::TYPE_ONE_TIME : 'recurring',
            'url' => [
                'update' => route('contact.reminder.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'reminder' => $reminder->id,
                ]),
                'destroy' => route('contact.reminder.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'reminder' => $reminder->id,
                ]),
            ],
        ];
    }
}
