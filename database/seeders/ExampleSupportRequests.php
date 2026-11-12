<?php

declare(strict_types=1);

namespace Database\Seeders;

/**
 * The support requests of the example account, one in each status, so both the support area and
 * the administrators' queue have something to show. The example user also runs the instance, so she
 * answers her own requests: an example account is one person.
 */
class ExampleSupportRequests
{
    /** @return list<array{category: string, subject: string, body: string, reply: ?string, note: ?string, after: string}> */
    public function all(): array
    {
        return [
            [
                'category' => 'technical',
                'subject' => 'Birthday reminders arrive a day late',
                'body' => 'Since I moved the vault to Scranton time, every birthday reminder lands the day after the birthday. Pam’s came on the 26th instead of the 25th.',
                'reply' => null,
                'note' => 'The account time zone is still London. Ask which one Settings shows before changing anything.',
                'after' => 'to_handle',
            ],
            [
                'category' => 'feature',
                'subject' => 'Sort people by last contacted',
                'body' => 'Sorting by the date I last talked to someone would show me who I am drifting away from.',
                'reply' => 'We like this a lot. Do you mean any recorded activity, or only calls and messages?',
                'note' => null,
                'after' => 'waiting_on_user',
            ],
            [
                'category' => 'question',
                'subject' => 'Can I share one contact without sharing the vault?',
                'body' => 'I want Jim to see Dwight’s page but nothing else in the vault.',
                'reply' => 'A share link does exactly that: it shows one record, read only, and you can revoke it whenever you like.',
                'note' => null,
                'after' => 'solved',
            ],
            [
                'category' => 'billing',
                'subject' => 'October invoice shows the wrong company name',
                'body' => 'The October invoice is made out to Dunder Mifflin Paper Company. It should say Dunder Mifflin Scranton, for the accounting department.',
                'reply' => null,
                'note' => null,
                'after' => 'to_handle',
            ],
            [
                'category' => 'email',
                'subject' => 'Reminder emails stopped in October',
                'body' => 'I have not received a single reminder email since the start of October. The spam folder is empty and my address has not changed.',
                'reply' => 'Our mail provider shows every reminder delivered to your address. Could you check whether your mail server filters messages before they reach your spam folder?',
                'note' => null,
                'after' => 'waiting_on_user',
            ],
        ];
    }
}
