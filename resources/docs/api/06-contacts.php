<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$vaultId = '9d4f1c2e-7b3a-4f88-9a2d-6c1e5b0f7a31';
$contactId = 'c4a1f7b2-8e36-4d59-b0a7-2f5c9d81e364';
$otherContactId = '7b2e9034-5c18-4af6-9d21-0e83b6c47a5f';
$vaultSlug = 'dunder-mifflin';

$contact = [
    'type' => 'contact',
    'id' => $contactId,
    'attributes' => [
        'slug' => 'pam-beesly',
        'name' => 'Pam Beesly',
        'prefix' => null,
        'first_name' => 'Pam',
        'middle_name' => null,
        'last_name' => 'Beesly',
        'suffix' => null,
        'maiden_name' => null,
        'nickname' => 'Pammy',
        'name_order' => 'given_first',
        'initials' => 'PB',
        'avatar_colour' => '#0ea5e9',
        'avatar' => [
            'id' => '3f6c1d90-4b27-4e85-a1c3-9d720f5b8e46',
            'url' => $base.'/vaults/'.$vaultId.'/files/3f6c1d90-4b27-4e85-a1c3-9d720f5b8e46',
        ],
        'is_favourite' => true,
        'is_archived' => false,
        'is_partial' => false,
        'is_me' => false,
        'structure_version_id' => 4,
        'template_name' => 'Default contact',
        'last_life_event_at' => null,
        'created_at' => 1756252800,
        'last_interaction_at' => null,
        'updated_at' => 1756339200,
    ],
    'links' => [
        'self' => $base.'/vaults/'.$vaultSlug.'/contacts/pam-beesly',
    ],
];

$otherContact = [
    'type' => 'contact',
    'id' => $otherContactId,
    'attributes' => [
        'slug' => 'dwight-kurt-schrute',
        'name' => 'Dwight Kurt Schrute',
        'prefix' => null,
        'first_name' => 'Dwight',
        'middle_name' => 'Kurt',
        'last_name' => 'Schrute',
        'suffix' => null,
        'maiden_name' => null,
        'nickname' => null,
        'name_order' => null,
        'initials' => 'DS',
        'avatar_colour' => '#22c55e',
        'avatar' => null,
        'is_favourite' => false,
        'is_archived' => false,
        'is_partial' => false,
        'is_me' => false,
        'structure_version_id' => 4,
        'template_name' => 'Default contact',
        'last_life_event_at' => 1756166400,
        'created_at' => 1756080000,
        'last_interaction_at' => 1756166400,
        'updated_at' => null,
    ],
    'links' => [
        'self' => $base.'/vaults/'.$vaultSlug.'/contacts/dwight-kurt-schrute',
    ],
];

$vaultParam = [
    'name' => 'vault',
    'type' => 'string',
    'required' => true,
    'description' => 'The vault the contact lives in, named either by the readable name it reports as slug or by the identifier it reports as id. Both answer.',
];

$contactParam = [
    'name' => 'contact',
    'type' => 'string',
    'required' => true,
    'description' => 'The contact, named either by the readable name it reports as slug or by the identifier it reports as id. Both answer. A readable name is rebuilt whenever the record is renamed, and the previous one stops answering, so a client that stores a reference stores the id. A name a record has released is never given to another. The contact has to be in the vault the path names: a contact of yours addressed through the wrong vault answers 404, the same as one that never existed. A contact absorbed by a merge answers 404 here, and the merge endpoints say what became of them.',
];

$identifier = [$vaultParam, $contactParam];

$listParams = [
    [
        'name' => 'filter',
        'type' => 'string',
        'required' => false,
        'description' => 'Which people to list: all, favourites or archived. Archived contacts are in the archived list and in no other, so all does not mean everybody the vault has ever held.',
        'default' => 'all',
    ],
    [
        'name' => 'query',
        'type' => 'string',
        'required' => false,
        'description' => 'A search term, up to 255 characters. It is matched against the whole name, ignoring what the language of the account calls a minor difference, so elodie finds Élodie in French. It is also matched against the nickname as it was typed, ignoring case.',
    ],
    [
        'name' => 'sort',
        'type' => 'string',
        'required' => false,
        'description' => 'Which order to answer in: name, nickname, last-spoke or added. A contact the order knows nothing about, somebody with no nickname when sorting by nickname, is at the end whichever direction is asked for.',
        'default' => 'name',
    ],
    [
        'name' => 'direction',
        'type' => 'string',
        'required' => false,
        'description' => 'Either asc or desc.',
        'default' => 'asc',
    ],
    [
        'name' => 'page',
        'type' => 'integer',
        'required' => false,
        'description' => 'Which page to answer with.',
        'default' => '1',
    ],
    [
        'name' => 'per_page',
        'type' => 'integer',
        'required' => false,
        'description' => 'How many contacts one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.',
        'default' => '25',
    ],
];

$contactAttributes = [
    ['name' => 'slug', 'type' => 'string', 'description' => 'The readable name the contact is addressed by, built from the whole name above and unique within the vault. It keeps the alphabet the name is written in, so a name in Japanese is addressed in Japanese, sent as its own characters or percent encoded. It is not stable: correcting the name rebuilds it and the previous one stops answering, which is why a stored reference should be the id. Nothing lets you choose or edit it.'],
    ['name' => 'name', 'type' => 'string', 'description' => 'The whole name, assembled from the parts below in the order this contact reads in. It is what the lists sort and search on, and it is built rather than stored, so it cannot be written directly.'],
    ['name' => 'prefix', 'type' => 'string', 'description' => 'What comes before the name, up to 50 characters, or null. Dr and Sir are prefixes.'],
    ['name' => 'first_name', 'type' => 'string', 'description' => 'The given name, up to 255 characters, or null. It is the only part required to write a contact down, and even it may be cleared afterwards.'],
    ['name' => 'middle_name', 'type' => 'string', 'description' => 'Up to 255 characters, or null.'],
    ['name' => 'last_name', 'type' => 'string', 'description' => 'The family name, up to 255 characters, or null.'],
    ['name' => 'suffix', 'type' => 'string', 'description' => 'What comes after the name, up to 50 characters, or null. Jr and PhD are suffixes.'],
    ['name' => 'maiden_name', 'type' => 'string', 'description' => 'Up to 255 characters, or null.'],
    ['name' => 'nickname', 'type' => 'string', 'description' => 'What this person is actually called, up to 255 characters, or null. It is searched as it was typed rather than folded, and it is what the whole name falls back to when no other part is set.'],
    ['name' => 'name_order', 'type' => 'string', 'description' => 'Either given_first or family_first, or null to leave it to the reader. It is a fact about this person rather than a preference of yours, which is what lets a Japanese name keep its order in a list of western ones.'],
    ['name' => 'initials', 'type' => 'string', 'description' => 'The one or two letters drawn when the contact has no picture. Built from the name, so it cannot be written.'],
    ['name' => 'avatar_colour', 'type' => 'string', 'description' => 'The background those initials are drawn on, as a hex colour. It is chosen when the contact is written down and never changes afterwards, so somebody who has learnt to spot a face in a list does not have it move.'],
    ['name' => 'avatar', 'type' => 'object', 'description' => 'The picture, or null when the contact is drawn as its initials.'],
    ['name' => 'avatar.id', 'type' => 'uuid', 'description' => 'The identifier of the file the picture was stored as. It is a file of the vault like any other, so it counts against your storage.'],
    ['name' => 'avatar.url', 'type' => 'string', 'description' => 'Where the picture is served from. This address is served by the application and needs a signed in session rather than a token: no endpoint here streams a vault\'s files yet, and a document is as private as the record it sits on.'],
    ['name' => 'is_favourite', 'type' => 'boolean', 'description' => 'Whether the contact sits at the top of the list.'],
    ['name' => 'is_archived', 'type' => 'boolean', 'description' => 'Whether the contact has been put away. An archived contact is out of every list but the archived one. Reading this is possible here; changing it is not, because no screen offers the gesture either.'],
    ['name' => 'is_partial', 'type' => 'boolean', 'description' => 'Whether the contact is somebody mentioned in a relationship and reduced to a first name. A partial contact is off every list until somebody fills them in, so it is listed by no filter at all.'],
    ['name' => 'is_me', 'type' => 'boolean', 'description' => 'Whether this contact represents you. Exactly one contact per vault is, it cannot be deleted, and nothing here hands the title to somebody else.'],
    ['name' => 'structure_version_id', 'type' => 'integer', 'description' => 'The published version of the template this contact renders from. It says what pages the contact has and what is written on them. Publishing a new version moves nobody: a contact stays on the version it points at until it is moved.'],
    ['name' => 'template_name', 'type' => 'string', 'description' => 'What that template is called, which is what the lists show beside a contact.'],
    ['name' => 'last_life_event_at', 'type' => 'timestamp', 'description' => 'When the contact last took part in a life event, as a count of seconds since 1970, or null when they never have. It says nothing about whether you were there.'],
    ['name' => 'last_interaction_at', 'type' => 'timestamp', 'description' => 'When you and the contact last took part in the same life event, as a count of seconds since 1970, or null when you never have. This is what "last spoke" reads, and what an inactivity reminder counts from.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the contact was written down, as a count of seconds since 1970 rather than a written date.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When the contact last changed, as a count of seconds, or null if it has not changed since it was written down.'],
];

$freshContactAttributes = array_values(array_filter(
    array_map(
        fn (array $field): array => $field['name'] === 'avatar'
            ? [...$field, 'description' => 'Always null here. A contact is written down before it has a picture, and putting one on is its own call.']
            : $field,
        $contactAttributes,
    ),
    fn (array $field): bool => ! str_starts_with($field['name'], 'avatar.'),
));

$freshContact = [...$contact, 'attributes' => [...$contact['attributes'], 'avatar' => null, 'updated_at' => null]];

$nameParams = [
    ['name' => 'prefix', 'type' => 'string', 'required' => true, 'description' => 'What comes before the name, up to 50 characters, or null. Send it even when it is null.', 'example' => null],
    ['name' => 'first_name', 'type' => 'string', 'required' => true, 'description' => 'The given name, up to 255 characters, or null. Unlike creating a contact, this may be null: a contact already written down may be reduced to a nickname.', 'example' => 'Pam'],
    ['name' => 'middle_name', 'type' => 'string', 'required' => true, 'description' => 'Up to 255 characters, or null.', 'example' => null],
    ['name' => 'last_name', 'type' => 'string', 'required' => true, 'description' => 'The family name, up to 255 characters, or null.', 'example' => 'Halpert'],
    ['name' => 'suffix', 'type' => 'string', 'required' => true, 'description' => 'What comes after the name, up to 50 characters, or null.', 'example' => null],
    ['name' => 'maiden_name', 'type' => 'string', 'required' => true, 'description' => 'Up to 255 characters, or null.', 'example' => 'Beesly'],
    ['name' => 'nickname', 'type' => 'string', 'required' => true, 'description' => 'Up to 255 characters, or null.', 'example' => 'Pammy'],
    ['name' => 'name_order', 'type' => 'string', 'required' => true, 'description' => 'Either given_first or family_first, or null to leave it to the reader.', 'example' => 'given_first'],
];

$writeParams = [
    ['name' => 'first_name', 'type' => 'string', 'required' => true, 'description' => 'The given name, up to 255 characters. It is the only thing this asks for: somebody met once is worth writing down with nothing else.', 'example' => 'Pam'],
    ['name' => 'prefix', 'type' => 'string', 'required' => false, 'description' => 'What comes before the name, up to 50 characters.'],
    ['name' => 'middle_name', 'type' => 'string', 'required' => false, 'description' => 'Up to 255 characters.'],
    ['name' => 'last_name', 'type' => 'string', 'required' => false, 'description' => 'The family name, up to 255 characters.', 'example' => 'Beesly'],
    ['name' => 'suffix', 'type' => 'string', 'required' => false, 'description' => 'What comes after the name, up to 50 characters.'],
    ['name' => 'maiden_name', 'type' => 'string', 'required' => false, 'description' => 'Up to 255 characters.'],
    ['name' => 'nickname', 'type' => 'string', 'required' => false, 'description' => 'What this person is actually called, up to 255 characters.'],
    ['name' => 'name_order', 'type' => 'string', 'required' => false, 'description' => 'Either given_first or family_first. Left out, the reader decides.'],
    ['name' => 'is_partial', 'type' => 'boolean', 'required' => false, 'description' => 'Whether this is somebody you know of rather than somebody you have written down. A partial contact is off every list until they are more than a first name. False when nothing says.'],
    ['name' => 'is_favourite', 'type' => 'boolean', 'required' => false, 'description' => 'Whether the contact sits at the top of the list. False when nothing says.'],
    ['name' => 'structure_version_id', 'type' => 'integer', 'required' => false, 'description' => 'The published version of one of your active contact templates, which says what the contact\'s page holds. Left out, the contact starts from whatever this vault publishes for contacts. A draft, a version belonging to somebody else, and a version describing something other than a contact are each refused.'],
];

$contactColumns = [
    ['source' => 'email', 'section' => null, 'field_key' => null, 'values' => ['pam@dundermifflin.com', 'pambeesly@gmail.com']],
    ['source' => 'phone', 'section' => null, 'field_key' => null, 'values' => ['+1 570 555 0162 (Work)']],
    ['source' => 'last-spoke', 'section' => null, 'field_key' => null, 'values' => []],
];

$otherContactColumns = [
    ['source' => 'email', 'section' => null, 'field_key' => null, 'values' => ['dwight@schrutefarms.com']],
    ['source' => 'phone', 'section' => null, 'field_key' => null, 'values' => []],
    ['source' => 'last-spoke', 'section' => null, 'field_key' => null, 'values' => ['3 weeks ago']],
];

$sectionId = '5e8b2c17-9f04-4d6a-b3e1-7a20c4d95f18';

$columns = [
    ['source' => 'field', 'section' => $sectionId, 'field_key' => 'company', 'heading' => 'Employer', 'default_heading' => 'Company', 'is_visible' => true, 'is_supplied' => true],
    ['source' => 'email', 'section' => null, 'field_key' => null, 'heading' => null, 'default_heading' => 'Email', 'is_visible' => true, 'is_supplied' => true],
    ['source' => 'template', 'section' => null, 'field_key' => null, 'heading' => null, 'default_heading' => 'Template', 'is_visible' => false, 'is_supplied' => true],
];

$columnFields = [
    ['name' => 'data', 'type' => 'array', 'description' => 'Every column after the name, in the order the screen shows them.'],
    ['name' => 'data[]', 'type' => 'object', 'description' => 'One column.'],
    ['name' => 'data[].source', 'type' => 'string', 'description' => 'What the column shows: nickname, email, phone, birthday, template, last-spoke, added, or field for a field of a template. The name is not a column: it is always shown, always first, and never listed.'],
    ['name' => 'data[].section', 'type' => 'uuid', 'description' => 'For a field, the identifier of the section holding it, or null. A section can sit in several templates, so one column covers every template placing it, in every version.'],
    ['name' => 'data[].field_key', 'type' => 'string', 'description' => 'For a field, its key inside the section, or null.'],
    ['name' => 'data[].heading', 'type' => 'string', 'description' => 'The heading somebody wrote, or null for the default.'],
    ['name' => 'data[].default_heading', 'type' => 'string', 'description' => 'What the column is called when nobody renamed it, in the language of the request. Read only.'],
    ['name' => 'data[].is_visible', 'type' => 'boolean', 'description' => 'Whether the screen shows the column. A hidden column keeps its place and its heading.'],
    ['name' => 'data[].is_supplied', 'type' => 'boolean', 'description' => 'False when no active contact template carries the field any more. The column is kept, and the list leaves it out until a template carries the field again. Read only.'],
    ['name' => 'meta', 'type' => 'object', 'description' => 'About the columns as a whole.'],
    ['name' => 'meta.is_default', 'type' => 'boolean', 'description' => 'True when the vault never chose, and these are the defaults.'],
];

$columnParams = [
    ['name' => 'columns', 'type' => 'array', 'required' => true, 'description' => 'Every column after the name, in the order the screen shows them. It replaces whatever the vault held. An empty array puts the defaults back; to show the name alone, send the columns with is_visible false.'],
    ['name' => 'columns.*.source', 'type' => 'string', 'required' => true, 'description' => 'One of the sources listed above. The same source may appear once, and the same field once.'],
    ['name' => 'columns.*.section', 'type' => 'uuid', 'required' => false, 'description' => 'Required when the source is field: the section holding it, which has to be one of yours.'],
    ['name' => 'columns.*.field_key', 'type' => 'string', 'required' => false, 'description' => 'Required when the source is field, up to 64 characters. The field has to be carried by the published version of one of your active contact templates, unless the vault already shows it, which lets a column no template supplies any more be kept.'],
    ['name' => 'columns.*.heading', 'type' => 'string', 'required' => false, 'description' => 'Up to 255 characters. Empty or null keeps the default heading.'],
    ['name' => 'columns.*.is_visible', 'type' => 'boolean', 'required' => true, 'description' => 'Whether the screen shows the column.'],
];

return [
    'name' => 'Contacts',
    'description' => 'The people a vault holds, their names, their pictures and the template version each one is read through.',
    'sections' => [
        [
            'id' => 'contacts-index',
            'returnFields' => [
                ...ApiDocsFields::page('contact', 'contact', 'contacts', $contactAttributes),
                ['name' => 'data[].columns', 'type' => 'array', 'description' => 'What the people screen of this vault shows beside the name, in its order. Hidden columns, and columns no template supplies any more, are left out.'],
                ['name' => 'data[].columns[]', 'type' => 'object', 'description' => 'One column.'],
                ['name' => 'data[].columns[].source', 'type' => 'string', 'description' => 'What the column shows, one of the sources the column endpoints below list.'],
                ['name' => 'data[].columns[].section', 'type' => 'uuid', 'description' => 'For a field of a template, the section holding it, or null.'],
                ['name' => 'data[].columns[].field_key', 'type' => 'string', 'description' => 'For a field of a template, its key inside the section, or null.'],
                ['name' => 'data[].columns[].values', 'type' => 'array', 'description' => 'What this person holds in the column, written the way the screen writes it. Empty when they hold nothing.'],
                ['name' => 'data[].columns[].values[]', 'type' => 'string', 'description' => 'One value.'],
            ],
            'title' => 'List contacts',
            'label' => 'List contacts',
            'method' => 'GET',
            'path' => '/vaults/{vault}/contacts',
            'examplePath' => '/vaults/'.$vaultId.'/contacts',
            'description' => 'List the people one of your vaults holds, narrowed and ordered the way the people screen narrows and orders them.',
            'body' => [
                'The default order is by name, not by when somebody was written down, so that a program and a person looking at the same vault are looking at the same list. The order always ends on the identifier, which is what stops a contact being skipped or repeated by a page boundary moving while you page through.',
                'Three groups of people are never in the list you asked for. Archived contacts are only in the archived filter. Partial contacts, somebody mentioned in a relationship and reduced to a first name, are in none of the three until they are filled in. And a deleted contact is in none of them at all.',
                'A vault holding nobody answers with an empty list rather than an error.',
                'Each contact also carries columns: what the people screen of this vault shows beside the name, in its order, and the values each one holds for this person. A column holding several values, somebody with two email addresses, lists them all. A column holding nothing lists nothing. The values are written the way the screen writes them, dates included, and the raw dates stay in the attributes. The columns are the ones chosen with the endpoints below, or the defaults until somebody chooses.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => [$vaultParam],
            'queryParams' => $listParams,
            'returns' => 'A page of contact objects, and how many there are in all. A vault belonging to somebody else answers 404.',
            'response' => [
                'data' => [
                    [...$contact, 'columns' => $contactColumns],
                    [...$otherContact, 'columns' => $otherContactColumns],
                ],
                'meta' => [
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 2,
                ],
            ],
        ],
        [
            'id' => 'contacts-show',
            'returnFields' => ApiDocsFields::one('contact', 'contact', $contactAttributes),
            'title' => 'Get a contact',
            'label' => 'Get a contact',
            'method' => 'GET',
            'path' => '/vaults/{vault}/contacts/{contact}',
            'examplePath' => '/vaults/'.$vaultId.'/contacts/'.$contactId,
            'description' => 'Retrieve one contact: their name, how they are drawn, and which structure their page renders from.',
            'body' => [
                'What is actually written on the contact, the dates, the addresses and the notes their template asks for, is not in here. That comes from the template version named below and is a surface of its own, still to be built. This endpoint answers with the part of a contact the product imposes rather than the part you designed.',
                'Reading a contact is not written to your account journal, and it does not count as somebody having consulted the record.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'returns' => 'A contact object. A contact belonging to somebody else answers 404, which is also what a contact that never existed answers, what a deleted one answers, and what one of your own contacts answers when the vault in the path is not the one holding them.',
            'response' => ['data' => $contact],
        ],
        [
            'id' => 'contacts-vcard',
            'title' => 'Get a contact as a vCard',
            'label' => 'Get a contact as a vCard',
            'method' => 'GET',
            'path' => '/vaults/{vault}/contacts/{contact}/vcard',
            'examplePath' => '/vaults/'.$vaultId.'/contacts/'.$contactId.'/vcard',
            'description' => 'Retrieve one contact as a vCard file, the same card a device synchronising over CardDAV is given.',
            'body' => [
                'This is the one endpoint here that does not answer in JSON. The body is the card itself, vCard 4.0, with a file name attached, so what you receive can be written straight to disk or handed to an address book application.',
                'The card carries the structured name, the nickname, the photo and the values the contact holds, each labelled as the structure labels it. Properties the product has no field for, because a device wrote them, are carried through untouched.',
                'Values of fields marked sensitive are left out unless the token presented carries the sensitive fields scope. A token minted for everything does not reach them: that scope has to be asked for by name.',
                'Life events, relationships, reminders, notes and files attached to the contact are not in the card. vCard has nowhere to put them.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'returns' => 'The card, as text. A contact belonging to somebody else answers 404, and so does one of your own addressed through the wrong vault.',
            'responseMediaType' => 'text/vcard',
        ],
        [
            'id' => 'contacts-me',
            'returnFields' => ApiDocsFields::one('contact', 'contact', $contactAttributes),
            'title' => 'Get the Me contact',
            'label' => 'Get the Me contact',
            'method' => 'GET',
            'path' => '/vaults/{vault}/contacts/me',
            'examplePath' => '/vaults/'.$vaultId.'/contacts/me',
            'description' => 'Retrieve the contact that represents you in a vault, without knowing its identifier first.',
            'body' => [
                'Every vault holds exactly one of these, and it cannot be deleted or merged away, so this address is stable in a way an identifier you stored last year is not. It is what an integration should resolve first when it needs to know who the account holder is.',
                'It answers the ordinary contact object. There is nothing special about the record beyond the flag, and everything you can do to a contact you can do to this one, apart from removing it.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => [$vaultParam],
            'returns' => 'A contact object. A vault belonging to somebody else answers 404, and so does a vault of your own that holds no Me contact yet.',
            'response' => ['data' => $contact],
        ],
        [
            'id' => 'contacts-create',
            'returnFields' => ApiDocsFields::one('contact', 'contact', $freshContactAttributes),
            'title' => 'Create a contact',
            'label' => 'Create a contact',
            'method' => 'POST',
            'path' => '/vaults/{vault}/contacts',
            'examplePath' => '/vaults/'.$vaultId.'/contacts',
            'description' => 'Write somebody down in one of your vaults.',
            'body' => [
                'A first name is all this asks for. Everything else about a person can be filled in later, and a product that refuses to write down somebody you have only just met is one people work around.',
                'The contact starts from the structure this vault publishes for contacts unless you name another. Whichever it is, the contact stays on that exact version until somebody moves it: publishing a new version of a template moves nobody.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => [$vaultParam],
            'bodyParams' => $writeParams,
            'returns' => 'The contact object as it now reads, including the name assembled from the parts you sent. A vault belonging to somebody else answers 404 and nobody is written down.',
            'responseStatus' => 201,
            'response' => ['data' => $freshContact],
        ],
        [
            'id' => 'contacts-name-update',
            'returnFields' => ApiDocsFields::one('contact', 'contact', $contactAttributes),
            'title' => 'Change a name',
            'label' => 'Change a name',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/contacts/{contact}/name',
            'examplePath' => '/vaults/'.$vaultId.'/contacts/'.$contactId.'/name',
            'description' => 'Replace every part of a contact\'s name at once.',
            'body' => [
                'This replaces rather than patches, so every part has to be in the body even when it is null. A request carrying nothing but a new last name would otherwise erase the prefix, the nickname and the rest, and nothing here disappears without being asked for.',
                'Every part may be null, including all of them together. A contact with no name still sorts, still searches and still draws initials: the product falls back to whatever it has rather than refusing you.',
                'What the lists sort and search on is rebuilt from the new name as it is written, in the language of your account, so the contact moves to its new place in the list at once.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'bodyParams' => $nameParams,
            'returns' => 'The contact object as it now reads. A contact belonging to somebody else, or reached through the wrong vault, answers 404 and is left as it was.',
            'response' => ['data' => $contact],
        ],
        [
            'id' => 'contacts-destroy',
            'title' => 'Delete a contact',
            'label' => 'Delete a contact',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/contacts/{contact}',
            'examplePath' => '/vaults/'.$vaultId.'/contacts/'.$contactId,
            'description' => 'Delete a contact, and keep everything they took part in.',
            'body' => [
                'Nothing the contact was part of is destroyed. Relationships, life events and files outlive them, because those describe things that happened rather than the row itself.',
                'The reminders about this person go quiet and say why, rather than disappearing. Private links onto their page stop opening.',
                'The contact representing you cannot be deleted, and asking answers 422. Relationships and life events are written from that contact\'s point of view, so a vault that lost it would be talking about nobody.',
                'The deletion is soft, so nothing is destroyed, but no endpoint here brings a contact back today. Treat it as final.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'returns' => 'Nothing at all. A contact belonging to somebody else answers 404, and so does one you have already deleted, so calling this twice does not report a second deletion.',
            'responseStatus' => 204,
        ],
        [
            'id' => 'contacts-favourite-update',
            'returnFields' => ApiDocsFields::one('contact', 'contact', $contactAttributes),
            'title' => 'Mark a favourite',
            'label' => 'Mark a favourite',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/contacts/{contact}/favourite',
            'examplePath' => '/vaults/'.$vaultId.'/contacts/'.$contactId.'/favourite',
            'description' => 'Say whether a contact sits at the top of the list.',
            'body' => [
                'Send the state you want rather than asking for a toggle. Two callers acting on a stale reading would each invert what the other had just written, and neither would end up with what it asked for. Sending the state means the worst a repeat can do is confirm what is already stored.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'favourite', 'type' => 'boolean', 'required' => true, 'description' => 'True to mark the contact a favourite, false to take the mark off. Sending the state it is already in is allowed and changes nothing.', 'example' => true],
            ],
            'returns' => 'The contact object as it now reads. A contact belonging to somebody else, or reached through the wrong vault, answers 404 and is left as it was.',
            'response' => ['data' => $contact],
        ],
        [
            'id' => 'contacts-avatar-update',
            'returnFields' => ApiDocsFields::one('contact', 'contact', $contactAttributes),
            'title' => 'Put a picture on a contact',
            'label' => 'Put a picture on a contact',
            'method' => 'POST',
            'path' => '/vaults/{vault}/contacts/{contact}/avatar',
            'examplePath' => '/vaults/'.$vaultId.'/contacts/'.$contactId.'/avatar',
            'description' => 'Upload the picture a contact is drawn as, in place of their initials.',
            'body' => [
                'Send the file as multipart form data, in a field called avatar. This is a POST rather than a PUT because PHP reads an uploaded file on a POST and on nothing else.',
                'The picture is cropped square and becomes a file of the vault like any other, which is what makes your storage quota apply to it. An upload that would take the account past what it has left answers 422 and the contact keeps whatever picture it had.',
                'A contact has one picture. Replacing it lets the previous file go rather than deleting it outright, because a vault\'s files outlive what pointed at them.',
                'The address the picture comes back under is served by the application and needs a signed in session rather than a token. No endpoint here streams a vault\'s files yet.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'avatar', 'type' => 'file', 'required' => true, 'description' => 'A jpeg, png or webp image, at most 5 MB. The type is checked on what the file actually is rather than on what its name claims.'],
            ],
            'returns' => 'The contact object as it now reads, with avatar filled in. A contact belonging to somebody else, or reached through the wrong vault, answers 404 and nothing is uploaded.',
            'response' => ['data' => $contact],
        ],
        [
            'id' => 'contacts-avatar-destroy',
            'returnFields' => ApiDocsFields::one('contact', 'contact', $freshContactAttributes),
            'title' => 'Remove a picture',
            'label' => 'Remove a picture',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/contacts/{contact}/avatar',
            'examplePath' => '/vaults/'.$vaultId.'/contacts/'.$contactId.'/avatar',
            'description' => 'Take the picture off a contact and draw their initials again.',
            'body' => [
                'The initials go back on the background the contact was given when it was written down. That background does not change, so somebody who has learnt to spot a face in a list does not have it move.',
                'The file behind the picture is let go rather than deleted, so a restored snapshot can still find it for a while.',
                'A contact that has no picture answers exactly the same way, so calling this twice does not report a second removal.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'returns' => 'The contact object as it now reads, with avatar null. A contact belonging to somebody else, or reached through the wrong vault, answers 404.',
            'response' => ['data' => $freshContact],
        ],
        [
            'id' => 'contacts-template-version-update',
            'returnFields' => ApiDocsFields::one('contact', 'contact', $contactAttributes),
            'title' => 'Move to another version',
            'label' => 'Move to another version',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/contacts/{contact}/template-version',
            'examplePath' => '/vaults/'.$vaultId.'/contacts/'.$contactId.'/template-version',
            'description' => 'Put a contact on another published version of the template their page renders from.',
            'body' => [
                'Publishing a new version of a template moves nobody. This is the only thing that does, and it moves one contact at a time, in either direction: forward onto what the template publishes now, or back onto a version it published before.',
                'Nothing written on the record is touched. What was there is snapshotted first, so the move can be undone.',
                'What you may name is deliberately narrow: a published version of the template this contact already renders from. A draft, a version of another template, and a version of another account each answer 404 rather than 422, because it is an address for something that is not on offer rather than a bad field.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => $identifier,
            'bodyParams' => [
                ['name' => 'structure_version_id', 'type' => 'integer', 'required' => false, 'description' => 'The published version to move onto. Leave it out to move onto whatever that template publishes now, which is how a contact catches up after a template is republished.', 'example' => 4],
            ],
            'returns' => 'The contact object as it now reads, on its new version. A contact belonging to somebody else, a version not on offer, or the wrong vault each answer 404 and the contact is left where it was.',
            'response' => ['data' => $contact],
        ],
        [
            'id' => 'contact-list-columns-show',
            'title' => 'Get the columns of the people screen',
            'label' => 'Get the list columns',
            'method' => 'GET',
            'path' => '/vaults/{vault}/contact-list-columns',
            'examplePath' => '/vaults/'.$vaultId.'/contact-list-columns',
            'description' => 'Read which columns the people screen of a vault shows beside the name, what they are called and in what order.',
            'body' => [
                'A vault that never chose answers with the defaults: email, phone and last spoke shown, the template hidden. The meta says which of the two you are reading.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => [$vaultParam],
            'returnFields' => $columnFields,
            'returns' => 'Every column after the name. A vault belonging to somebody else answers 404.',
            'response' => ['data' => $columns, 'meta' => ['is_default' => false]],
        ],
        [
            'id' => 'contact-list-columns-update',
            'title' => 'Choose the columns of the people screen',
            'label' => 'Choose the list columns',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/contact-list-columns',
            'examplePath' => '/vaults/'.$vaultId.'/contact-list-columns',
            'description' => 'Replace the columns the people screen of a vault shows, in one call.',
            'body' => [
                'This changes what the screen shows and nothing written on any contact. It is written to your account journal.',
                'Asking for the same column twice, or for a field no template carries, answers 422 and leaves the columns as they were.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'pathParams' => [$vaultParam],
            'bodyParams' => $columnParams,
            'exampleBody' => ['columns' => [
                ['source' => 'field', 'section' => $sectionId, 'field_key' => 'company', 'heading' => 'Employer', 'is_visible' => true],
                ['source' => 'email', 'heading' => null, 'is_visible' => true],
            ]],
            'returnFields' => $columnFields,
            'returns' => 'The columns as they now stand. A vault or a section belonging to somebody else answers 404.',
            'response' => ['data' => $columns, 'meta' => ['is_default' => false]],
        ],
    ],
];
