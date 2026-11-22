<?php

declare(strict_types=1);

return [
    'date' => '2026-11-21',
    'entries' => [
        [
            'id' => 'contact-name-order-follows-language',
            'kind' => 'Changed',
            'group' => 'Contacts',
            'title' => 'Ordering by name follows the language of the account',
            'summary' => 'sort=name now files people the way the language of the key owner\'s account does: by given name in Icelandic, Sinhala, Tamil and Vietnamese, after a particle such as van, von, de or dos in Dutch, German, French, Spanish and Portuguese, with the Turkish dotted and dotless i kept apart, and by the pronunciation written in kana in Japanese. Every other language files by family name as before. No field changes, and a search finds what it found before.',
            'why' => 'Every list filed people by family name in every language, which is not where an Icelandic or a Vietnamese reader looks for them.',
            'breaking' => false,
            'changes' => [
                ['field' => 'sort=name', 'change' => 'Changed', 'endpoints' => ['GET /vaults/{vault}/contacts']],
            ],
        ],
    ],
];
