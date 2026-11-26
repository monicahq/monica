<?php

declare(strict_types=1);

return [
    'date' => '2026-11-26',
    'entries' => [
        [
            'id' => 'contacts-deletion-reports-missing',
            'kind' => 'Changed',
            'group' => 'Contacts',
            'title' => 'Deleting several contacts skips an id it does not find and lists it',
            'summary' => 'DELETE /vaults/{vault}/contacts no longer answers 422 when an id matches no contact of the vault. That id is skipped, the others are deleted, and the answer lists it in data.attributes.missing, as given and without repeats. When every id is unknown the answer is 200 with nothing deleted.',
            'why' => 'One wrong id used to refuse the whole batch, and the caller was not told which id it was.',
            'breaking' => true,
            'impact' => 'An integration that relied on the 422 for an unknown id now gets 200. Read data.attributes.missing to find what was not deleted.',
            'changes' => [
                ['field' => 'data.attributes.missing', 'change' => 'Added', 'endpoints' => ['DELETE /vaults/{vault}/contacts']],
                ['field' => 'contacts', 'change' => 'Changed', 'endpoints' => ['DELETE /vaults/{vault}/contacts']],
            ],
        ],
        [
            'id' => 'support-messages-markdown',
            'kind' => 'Changed',
            'group' => 'Support requests',
            'title' => 'Support messages are written in Markdown',
            'summary' => 'The body of a support request, a message, a reply and a note is now Markdown, kept exactly as written. It is no longer stripped of anything that looks like an HTML tag, so it can contain < and >. Format it safely before displaying it, escaping raw HTML and refusing links that are not web or email links, and never insert it into a page as HTML.',
            'why' => 'The support screens now show messages formatted, and a request often holds steps and pasted errors that read better as lists and code.',
            'breaking' => false,
            'changes' => [
                ['field' => 'body', 'change' => 'Changed', 'endpoints' => [
                    'GET /support-requests/{supportRequest}',
                    'POST /support-requests',
                    'POST /support-requests/{supportRequest}/messages',
                    'GET /admin/support-requests/{supportRequest}',
                    'POST /admin/support-requests/{supportRequest}/replies',
                    'POST /admin/support-requests/{supportRequest}/notes',
                ]],
            ],
        ],
        [
            'id' => 'relationships-recorded-once',
            'kind' => 'Changed',
            'group' => 'Relationships',
            'title' => 'The same relationship cannot be recorded twice',
            'summary' => 'POST /vaults/{vault}/contacts/{contact}/relationships answers 422 on other_contact when the two people already hold a current relationship of that type the same way round. A symmetric type has only one way round, so linking B to A after A to B is refused. A relationship that ended does not count. PUT /vaults/{vault}/contacts/{contact}/relationships/{relationship} answers 422 on relationship_type when the change would leave the two people with two current relationships of the same type.',
            'why' => 'Two rows for one friendship listed it twice on both contact pages.',
            'breaking' => false,
            'changes' => [
                ['field' => 'other_contact', 'change' => 'Changed', 'endpoints' => ['POST /vaults/{vault}/contacts/{contact}/relationships']],
                ['field' => 'relationship_type', 'change' => 'Changed', 'endpoints' => ['PUT /vaults/{vault}/contacts/{contact}/relationships/{relationship}']],
            ],
        ],
    ],
];
