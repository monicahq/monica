<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$vaultId = '9d4f1c2e-7b3a-4f88-9a2d-6c1e5b0f7a31';
$contactId = 'c4a1f7b2-8e36-4d59-b0a7-2f5c9d81e364';
$otherContactId = '7b2e9034-5c18-4af6-9d21-0e83b6c47a5f';
$mergeId = 'e58c3a17-90d2-4b6e-8f14-7a3c05d9b2e8';

$vaultParam = [
    'name' => 'vault',
    'type' => 'uuid',
    'required' => true,
    'description' => 'The id of the vault the contacts live in. A path that is not a uuid answers 404 without a query being run.',
];

$pairParams = [
    $vaultParam,
    ['name' => 'candidate', 'type' => 'integer', 'required' => true, 'description' => 'The id of a pair this vault proposed.'],
];

$mergeParams = [
    $vaultParam,
    ['name' => 'contact', 'type' => 'uuid', 'required' => true, 'description' => 'The contact that survives. Everything the other holds comes to it.'],
    ['name' => 'other', 'type' => 'uuid', 'required' => true, 'description' => 'The contact that is absorbed. It is kept, soft deleted, and its address lands on the survivor.'],
];

$candidateAttributes = [
    ['name' => 'contact_id', 'type' => 'string', 'description' => 'One of the two contacts.'],
    ['name' => 'other_contact_id', 'type' => 'string', 'description' => 'The other one.'],
    ['name' => 'contact_name', 'type' => 'string', 'description' => 'What the first one is called, so a list reads without a second request.'],
    ['name' => 'other_contact_name', 'type' => 'string', 'description' => 'What the second one is called.'],
    ['name' => 'score', 'type' => 'integer', 'description' => 'How strongly the two resemble each other, 0 to 100. It orders the list and says nothing about certainty.'],
    ['name' => 'reasons', 'type' => 'array', 'description' => 'One entry per signal that fired.'],
    ['name' => 'reasons[]', 'type' => 'object', 'description' => 'One signal.'],
    ['name' => 'reasons[].kind', 'type' => 'string', 'description' => 'Either name, when the two names resemble each other, or field, when the two share a value in a field the structure marks as identifying.'],
    ['name' => 'reasons[].field_key', 'type' => 'string', 'description' => 'The key of that field. Only on a field signal.'],
    ['name' => 'reasons[].value', 'type' => 'string', 'description' => 'The value the two share. Only on a field signal.'],
    ['name' => 'dismissed_at', 'type' => 'integer', 'description' => 'When somebody said these are two different people, null while the pair stands.'],
    ['name' => 'computed_at', 'type' => 'integer', 'description' => 'When detection last looked at this pair.'],
];

$mergeAttributes = [
    ['name' => 'kept_contact_id', 'type' => 'string', 'description' => 'The contact that survived.'],
    ['name' => 'merged_contact_id', 'type' => 'string', 'description' => 'The contact that was absorbed. It is soft deleted, not destroyed.'],
    ['name' => 'resolutions', 'type' => 'array', 'description' => 'What was decided at every difference, exactly as it was sent.'],
    ['name' => 'resolutions[]', 'type' => 'object', 'description' => 'One decision.'],
    ['name' => 'resolutions[].field_key', 'type' => 'string', 'description' => 'The field it was about.'],
    ['name' => 'resolutions[].row', 'type' => 'integer', 'description' => 'The repeater entry, null for a plain difference.'],
    ['name' => 'resolutions[].keep', 'type' => 'string', 'description' => 'What was kept: kept, other or both.'],
    ['name' => 'moved', 'type' => 'array', 'description' => 'What moved, row by row. It is what the undo replays backwards.'],
    ['name' => 'moved[]', 'type' => 'object', 'description' => 'One thing that moved.'],
    ['name' => 'moved[].kind', 'type' => 'string', 'description' => 'What happened, such as value_moved, row_moved, reminder_moved, value_detached or name_representation_moved.'],
    ['name' => 'moved[].id', 'type' => 'integer', 'description' => 'The row it happened to.'],
    ['name' => 'moved[].from', 'type' => 'integer', 'description' => 'The contact it came from, where the entry names one.'],
    ['name' => 'moved[].position', 'type' => 'integer', 'description' => 'For a way of writing the name that came over, where it stood in the list of the contact it came from.'],
    ['name' => 'stands', 'type' => 'boolean', 'description' => 'Whether the merge is still in force. False once it has been undone.'],
    ['name' => 'undone_at', 'type' => 'integer', 'description' => 'When it was undone, null while it stands.'],
    ['name' => 'created_at', 'type' => 'integer', 'description' => 'When the merge ran.'],
];

$previewFields = [
    ['name' => 'data', 'type' => 'object', 'description' => 'The preview.'],
    ['name' => 'data.type', 'type' => 'string', 'description' => 'Always contact_merge_preview.'],
    ['name' => 'data.attributes', 'type' => 'object', 'description' => 'Everything the preview reports.'],
    ['name' => 'data.attributes.kept', 'type' => 'object', 'description' => 'The surviving contact.'],
    ['name' => 'data.attributes.kept.uuid', 'type' => 'string', 'description' => 'Its id.'],
    ['name' => 'data.attributes.kept.name', 'type' => 'string', 'description' => 'What it is called.'],
    ['name' => 'data.attributes.other', 'type' => 'object', 'description' => 'The contact that would be absorbed.'],
    ['name' => 'data.attributes.other.uuid', 'type' => 'string', 'description' => 'Its id.'],
    ['name' => 'data.attributes.other.name', 'type' => 'string', 'description' => 'What it is called.'],
    ['name' => 'data.attributes.conflicts', 'type' => 'array', 'description' => 'Every place the two disagree. Each one has to be decided before the merge will run.'],
    ['name' => 'data.attributes.conflicts[]', 'type' => 'object', 'description' => 'One disagreement.'],
    ['name' => 'data.attributes.conflicts[].field_key', 'type' => 'string', 'description' => 'The key of the field, @avatar for the picture, or @display_name for the name shown. No field key starts with @. The name shown may be left undecided, and the surviving contact then keeps its own.'],
    ['name' => 'data.attributes.conflicts[].field_id', 'type' => 'integer', 'description' => 'The field, null for the picture and for the name shown.'],
    ['name' => 'data.attributes.conflicts[].label', 'type' => 'string', 'description' => 'What the field is called on the page.'],
    ['name' => 'data.attributes.conflicts[].row', 'type' => 'integer', 'description' => 'The repeater entry, null for a plain difference.'],
    ['name' => 'data.attributes.conflicts[].kept_value', 'type' => 'string', 'description' => 'What the surviving contact holds.'],
    ['name' => 'data.attributes.conflicts[].other_value', 'type' => 'string', 'description' => 'What the other one holds.'],
    ['name' => 'data.attributes.moves', 'type' => 'array', 'description' => 'How much of each kind of thing would come over. A kind with nothing to move is left out.'],
    ['name' => 'data.attributes.moves[]', 'type' => 'object', 'description' => 'One kind of thing.'],
    ['name' => 'data.attributes.moves[].kind', 'type' => 'string', 'description' => 'One of name_representation, field_value, field_value_row, reminder, share_link, group_membership or relation_pointer.'],
    ['name' => 'data.attributes.moves[].count', 'type' => 'integer', 'description' => 'How many of them.'],
    ['name' => 'data.attributes.collisions', 'type' => 'array', 'description' => 'Repeater entries the merge would place identically twice. Each one has to be decided too. Empty when there are none.'],
    ['name' => 'data.attributes.set_aside', 'type' => 'array', 'description' => 'Answers with no matching question on the surviving structure. They are kept out of the way rather than thrown away. Empty when there are none.'],
    ['name' => 'data.attributes.folds', 'type' => 'array', 'description' => 'Groups both contacts are current members of. The two memberships become one, from the earlier join date, with the surviving contact\'s role unless it has none. Empty when there are none.'],
    ['name' => 'data.attributes.folds[]', 'type' => 'object', 'description' => 'One group whose two memberships become one.'],
    ['name' => 'data.attributes.folds[].group', 'type' => 'string', 'description' => 'The id of the group.'],
    ['name' => 'data.attributes.folds[].name', 'type' => 'string', 'description' => 'What the group is called.'],
    ['name' => 'data.attributes.fingerprint', 'type' => 'string', 'description' => 'What the preview was built on. Hand it back when you merge and the merge is refused if either contact changed in between.'],
];

$candidate = [
    'type' => 'contact_duplicate_candidate',
    'id' => '42',
    'attributes' => [
        'contact_id' => $contactId,
        'other_contact_id' => $otherContactId,
        'contact_name' => 'Jim Halpert',
        'other_contact_name' => 'Jimmy Halpert',
        'score' => 95,
        'reasons' => [
            ['kind' => 'field', 'field_key' => 'email', 'value' => 'jim@dundermifflin.com'],
            ['kind' => 'name'],
        ],
        'dismissed_at' => null,
        'computed_at' => 1757462400,
    ],
];

$dismissed = $candidate;
$dismissed['attributes']['dismissed_at'] = 1757548800;

$preview = [
    'type' => 'contact_merge_preview',
    'attributes' => [
        'kept' => ['uuid' => $contactId, 'name' => 'Jim Halpert'],
        'other' => ['uuid' => $otherContactId, 'name' => 'Jimmy Halpert'],
        'conflicts' => [
            [
                'field_key' => 'email',
                'field_id' => 12,
                'label' => 'Email',
                'row' => null,
                'kept_value' => 'jim@dundermifflin.com',
                'other_value' => 'jimmy@dundermifflin.com',
            ],
        ],
        'moves' => [
            ['kind' => 'field_value', 'count' => 1],
        ],
        'collisions' => [],
        'set_aside' => [],
        'folds' => [
            ['group' => '0199a2b4-7c1d-7e3f-9a40-5b6c7d8e9f01', 'name' => 'Halpert household'],
        ],
        'fingerprint' => '5f2c9d1b7a34e806c5f19d2b48a7361ce0947fb52d8a1c6e3947b0d85f2a1c63',
    ],
];

$merge = [
    'type' => 'contact_merge',
    'id' => $mergeId,
    'attributes' => [
        'kept_contact_id' => $contactId,
        'merged_contact_id' => $otherContactId,
        'resolutions' => [
            ['field_key' => 'email', 'row' => null, 'keep' => 'kept'],
        ],
        'moved' => [
            ['kind' => 'name_representation_moved', 'id' => 64, 'from' => 27, 'position' => 2],
            ['kind' => 'value_moved', 'id' => 811, 'from' => 27],
        ],
        'stands' => true,
        'undone_at' => null,
        'created_at' => 1757548800,
    ],
];

return [
    'name' => 'Duplicates and merges',
    'description' => 'Contacts that look like the same person, and the act of folding one into another without losing what either held.',
    'sections' => [
        [
            'id' => 'duplicates-index',
            'returnFields' => ApiDocsFields::page('contact_duplicate_candidate', 'pair', 'pairs', $candidateAttributes, withLinks: false),
            'title' => 'List possible duplicates',
            'label' => 'List possible duplicates',
            'method' => 'GET',
            'path' => '/vaults/{vault}/duplicates',
            'examplePath' => '/vaults/'.$vaultId.'/duplicates',
            'description' => 'List the pairs of contacts in one of your vaults that look like the same person, strongest resemblance first.',
            'body' => [
                'Two signals put a pair on this list. Names that resemble each other, and an exact shared value in a field your structure marks as identifying, which is what catches a pair whose names do not resemble each other at all. The reasons say which fired.',
                'The list is worked out on a queue, not while you are reading it. Creating or renaming a contact queues a look at that contact, and a nightly pass looks at every vault, which is what notices a template that has just gained an identifying field. So a pair created a second ago may not be here yet.',
                'A pair somebody has ruled out is never in the list again. A vault with nothing doubled answers with an empty list rather than an error.',
                'Nothing here merges anything. This says two records might be one person, and a person decides.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => [$vaultParam],
            'returns' => 'A page of pairs, and how many there are in all. A vault belonging to somebody else answers 404.',
            'response' => [
                'data' => [$candidate],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'duplicates-dismissal-update',
            'returnFields' => ApiDocsFields::one('contact_duplicate_candidate', 'pair', $candidateAttributes, withLinks: false),
            'title' => 'Rule a pair out',
            'label' => 'Rule a pair out',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/duplicates/{candidate}/dismissal',
            'examplePath' => '/vaults/'.$vaultId.'/duplicates/42/dismissal',
            'description' => 'Say that two contacts the product proposed are two different people.',
            'body' => [
                'This is permanent. Detection keeps recomputing and leaves the pair alone from now on, so it is never raised again even though the two still resemble each other, which they will.',
                'Ruling out a pair that is already ruled out answers exactly the same way and changes nothing, so calling this twice is safe.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $pairParams,
            'returns' => 'The pair as it now reads, with dismissed_at set. A pair in another vault or another account answers 404.',
            'response' => ['data' => $dismissed],
        ],
        [
            'id' => 'contacts-merge-show',
            'returnFields' => $previewFields,
            'title' => 'Preview a merge',
            'label' => 'Preview a merge',
            'method' => 'GET',
            'path' => '/vaults/{vault}/contacts/{contact}/merge/{other}',
            'examplePath' => '/vaults/'.$vaultId.'/contacts/'.$contactId.'/merge/'.$otherContactId,
            'description' => 'Work out what merging one contact into another would do, without doing any of it.',
            'body' => [
                'Ask this first. Whatever it reports is what the merge is then allowed to write: a decision it never asked about is refused, and a difference it did ask about and you left undecided is refused too.',
                'A difference is a place where both contacts hold a value and the two do not agree. A value only one of them holds is not a difference, it simply comes over.',
                'Keep the fingerprint. Hand it back when you merge, and if either contact changed in between the merge is refused rather than applied to a state nobody looked at.',
                'The two contacts have to be in the same vault. Merging a contact with itself, and absorbing the contact that represents you, are both refused.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $mergeParams,
            'returns' => 'What the merge would do. A contact belonging to somebody else, or reached through the wrong vault, answers 404. The same contact twice, or the contact representing you as the one absorbed, answers 422. While an import writes into the vault, or rolls back what it wrote, the preview answers 409.',
            'response' => ['data' => $preview],
        ],
        [
            'id' => 'contacts-merge-create',
            'returnFields' => ApiDocsFields::one('contact_merge', 'merge', $mergeAttributes, withLinks: false),
            'title' => 'Merge two contacts',
            'label' => 'Merge two contacts',
            'method' => 'POST',
            'path' => '/vaults/{vault}/contacts/{contact}/merge/{other}',
            'examplePath' => '/vaults/'.$vaultId.'/contacts/'.$contactId.'/merge/'.$otherContactId,
            'description' => 'Make two contacts one, keeping everything either of them held.',
            'body' => [
                'Everything attached to the absorbed contact comes over: values, repeater entries, files, the picture, reminders, shared links, and the relation fields other records pointed at it through.',
                'Nothing is destroyed. The absorbed contact is soft deleted and keeps its own rows, so a value you chose not to bring over is still there. A value the survivor gave up is set aside rather than deleted, and so is a value whose field the surviving structure does not carry.',
                'The state of the survivor is captured before anything is written, and what moved is recorded, so the whole thing can be undone.',
                'Send one resolution for every difference and collision the preview reported, and none it did not. keep is kept or other for a difference, kept or both for a collision.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $mergeParams,
            'bodyParams' => [
                ['name' => 'fingerprint', 'type' => 'string', 'required' => false, 'description' => 'What the preview was built on. Leave it out and the merge runs against whatever the two contacts hold now, which is only safe if nothing else is writing.', 'example' => '5f2c9d1b7a34e806c5f19d2b48a7361ce0947fb52d8a1c6e3947b0d85f2a1c63'],
                ['name' => 'resolutions', 'type' => 'array', 'required' => false, 'description' => 'One entry per difference and collision the preview reported. Leave it out only when the preview reported none.', 'example' => null],
                ['name' => 'resolutions[].field_key', 'type' => 'string', 'required' => true, 'description' => 'The key of the field the preview named.', 'example' => null],
                ['name' => 'resolutions[].row', 'type' => 'integer', 'required' => false, 'description' => 'The repeater entry, for a collision. Null or absent for a plain difference.', 'example' => null],
                ['name' => 'resolutions[].keep', 'type' => 'string', 'required' => true, 'description' => 'kept or other for a difference. kept or both for a collision.', 'example' => null],
            ],
            'returns' => 'The record of the merge. A contact belonging to somebody else answers 404. A resolution the preview never reported, one left undecided, and absorbing the contact representing you each answer 422. Merging two partial contacts makes the survivor a full one, so on the free plan it answers 422, under other, with the wording of the plan, when the account already holds every contact the plan allows. A contact changed since the preview answers 409, and nothing is written. So does a merge asked for while an import writes into the vault or rolls back what it wrote.',
            'responseStatus' => 201,
            'response' => ['data' => $merge],
        ],
        [
            'id' => 'merges-destroy',
            'title' => 'Undo a merge',
            'label' => 'Undo a merge',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/merges/{merge}',
            'examplePath' => '/vaults/'.$vaultId.'/merges/'.$mergeId,
            'description' => 'Put two merged contacts back the way they were.',
            'body' => [
                'It replays what the merge recorded, backwards, and brings the absorbed contact back. Everything that moved returns to the contact it came from, a value that was set aside is attached again, and a reminder the merge stopped starts again.',
                'Either the whole thing comes back or nothing does. An undo that cannot be completed is refused before anything is written, so a half-reversed merge is not a state you can reach.',
                'A merge stays undoable while the absorbed contact is retained, and nothing purges one today. A merge that has already been undone, and one whose absorbed contact is gone for good, are both refused.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => [
                $vaultParam,
                ['name' => 'merge', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the merge to undo.'],
            ],
            'returns' => 'Nothing at all. A merge in another vault or another account answers 404. One already undone, or one whose absorbed contact is gone, answers 422 and nothing changes.',
            'responseStatus' => 204,
        ],
    ],
];
