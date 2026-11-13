<?php

declare(strict_types=1);

return [
    'date' => '2026-11-13',
    'entries' => [
        [
            'id' => 'contact-markdown',
            'kind' => 'Added',
            'group' => 'Contacts',
            'title' => 'Adds a contact as one Markdown document',
            'summary' => 'Get everything recorded about a contact (names, fields, relationships, life events, groups, reminders and loans) as one Markdown document, the same text the contact page downloads. Sensitive values need the sensitive fields scope.',
            'why' => 'Reading a whole contact took one request per kind of data, and an assistant had to put the answers back together itself.',
            'changes' => [
                ['field' => 'markdown', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/contacts/{contact}/markdown']],
            ],
        ],
        [
            'id' => 'vault-reminder-occurrences',
            'kind' => 'Added',
            'group' => 'Reminders',
            'title' => 'Adds the reminder dates of a whole vault',
            'summary' => 'List what is overdue, due today and coming up across every contact, group and record of a vault, filtered by category, by kind (every reminder or birthdays only) and by how many days ahead. Birthdays come with the age being reached, and the birthdays nobody asked to be reminded of are listed too.',
            'why' => 'The dates of a vault could only be read one contact or one group at a time, so the question the vault dashboard answers took one request per person.',
            'changes' => [
                ['field' => 'reminder-occurrences', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/reminder-occurrences']],
            ],
        ],
        [
            'id' => 'possession-field-removed-choice-values',
            'kind' => 'Changed',
            'group' => 'Possession types',
            'title' => 'Removing a choice removes the values that held it',
            'summary' => 'When a change to a select field leaves out a choice, every possession that held that choice loses its value for the field. The deletion is final.',
            'why' => 'A possession could keep a value that was no longer a choice. Writing that value back was refused, and the web form replaced it with no value without saying so.',
            'changes' => [
                ['field' => 'options', 'change' => 'Changed', 'endpoints' => ['PUT /possession-types/{possessionType}/fields/{field}']],
            ],
        ],
    ],
];
