<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;

$vaultSlug = 'dunder-mifflin';
$janId = '7c2e9a41-5b8d-4f63-a1e0-3d94b6f2c815';
$lifeEventId = 'e4a71b2c-9d36-4f85-b0c3-61a8d2f7e940';

$kinds = 'contact, entity, group, relationship, life-event, reminder, group-membership, group-note or group-file';

$item = [
    'type' => 'trash_item',
    'id' => $lifeEventId,
    'attributes' => [
        'kind' => 'life-event',
        'title' => 'Opened Serenity by Jan',
        'type_label' => 'Life event',
        'context' => 'About Jan Levinson',
        'deleted_at' => 1795600800,
        'deleted_for_good_at' => 1798192800,
        'deleted_with' => [
            'kind' => 'contact',
            'id' => $janId,
            'title' => 'Jan Levinson',
        ],
        'can_be_restored' => false,
        'obstacle' => 'Deleted with Jan Levinson. Restore Jan Levinson to bring this back.',
    ],
];

$itemAttributes = [
    ['name' => 'kind', 'type' => 'string', 'description' => "What the item is: {$kinds}."],
    ['name' => 'title', 'type' => 'string', 'description' => 'What the item is called.'],
    ['name' => 'type_label', 'type' => 'string', 'description' => 'The word naming its kind, in the language of the key. A record says the name of its own type, such as Animal.'],
    ['name' => 'context', 'type' => 'string', 'description' => 'Where it belonged, in a few words, or null.'],
    ['name' => 'deleted_at', 'type' => 'timestamp', 'description' => 'When it was deleted, as a count of seconds since 1970.'],
    ['name' => 'deleted_for_good_at', 'type' => 'timestamp', 'description' => 'When it is deleted for good unless restored before, as a count of seconds since 1970.'],
    ['name' => 'deleted_with', 'type' => 'object', 'description' => 'The person or the group it was deleted with, while that one is still in the trash. Null otherwise.'],
    ['name' => 'deleted_with.kind', 'type' => 'string', 'description' => 'contact or group.'],
    ['name' => 'deleted_with.id', 'type' => 'uuid', 'description' => 'The id of the person or the group, which addresses it in the trash.'],
    ['name' => 'deleted_with.title', 'type' => 'string', 'description' => 'Their name.'],
    ['name' => 'can_be_restored', 'type' => 'boolean', 'description' => 'Whether restoring it now would work.'],
    ['name' => 'obstacle', 'type' => 'string', 'description' => 'Why it cannot be restored yet, or null when it can.'],
];

$vaultIdentifier = [
    ['name' => 'vault', 'type' => 'string', 'required' => true, 'description' => 'The vault the trash belongs to, named either by the readable name it reports as slug or by its id.'],
];

$itemIdentifier = [
    ...$vaultIdentifier,
    ['name' => 'kind', 'type' => 'string', 'required' => true, 'description' => "The kind of the item, as the list gives it: {$kinds}."],
    ['name' => 'item', 'type' => 'uuid', 'required' => true, 'description' => 'The id of the item, as the list gives it. One not in the trash of this vault, or belonging to another account, answers 404.'],
];

return [
    'name' => 'Trash',
    'description' => 'Whatever is deleted from a vault waits in its trash for 30 days, then it is deleted for good. Deleting a person takes their relationships, group memberships, reminders and the life events nobody else takes part in; deleting a group takes its memberships, notes, files and reminders. Restoring one brings back what went with it.',
    'sections' => [
        [
            'id' => 'trash-index',
            'returnFields' => ApiDocsFields::page('trash_item', 'item', 'items', $itemAttributes, withLinks: false),
            'title' => 'List the trash of a vault',
            'label' => 'List the trash',
            'method' => 'GET',
            'path' => '/vaults/{vault}/trash',
            'examplePath' => '/vaults/'.$vaultSlug.'/trash?kind=life-event',
            'description' => 'List what was deleted from a vault and still waits in its trash, the most recently deleted first.',
            'body' => [
                'Without a kind, an item deleted with a person or a group still in the trash is left out, since it comes back with them. With a kind, every item of that kind is listed.',
                'A kind that is not available to the account, such as groups while they are not, lists nothing.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $vaultIdentifier,
            'queryParams' => [
                ['name' => 'kind', 'type' => 'string', 'required' => false, 'description' => "Only the items of one kind: {$kinds}. Anything else answers 422 on kind.", 'example' => 'life-event'],
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page to answer with.', 'default' => '1', 'example' => 1],
                ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'How many items one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.', 'default' => '25', 'example' => 25],
            ],
            'returns' => 'A page of trash items, and how many there are in all. A vault of another account answers 404.',
            'response' => [
                'data' => [$item],
                'meta' => ['page' => 1, 'per_page' => 25, 'total' => 1],
            ],
        ],
        [
            'id' => 'trash-empty',
            'title' => 'Empty the trash of a vault',
            'label' => 'Empty the trash',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/trash',
            'examplePath' => '/vaults/'.$vaultSlug.'/trash',
            'description' => 'Delete everything in the trash of a vault for good. This cannot be undone.',
            'body' => [
                'The files the items held are let go of, and are removed from storage once nothing else points at them.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $vaultIdentifier,
            'returns' => 'Nothing at all. A vault of another account answers 404.',
            'responseStatus' => 204,
        ],
        [
            'id' => 'trash-destroy',
            'title' => 'Delete an item of the trash for good',
            'label' => 'Delete an item for good',
            'method' => 'DELETE',
            'path' => '/vaults/{vault}/trash/{kind}/{item}',
            'examplePath' => '/vaults/'.$vaultSlug.'/trash/contact/'.$janId,
            'description' => 'Delete one item of the trash for good, with everything deleted at the same time as it. This cannot be undone.',
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $itemIdentifier,
            'returns' => 'Nothing at all.',
            'responseStatus' => 204,
        ],
        [
            'id' => 'trash-restoration-update',
            'returnFields' => ApiDocsFields::one('trash_item', 'item', [
                ['name' => 'kind', 'type' => 'string', 'description' => 'What the item is.'],
                ['name' => 'title', 'type' => 'string', 'description' => 'What the item is called.'],
                ['name' => 'restored_with', 'type' => 'array', 'description' => 'What came back with it, one entry per kind. Empty when nothing did.'],
                ['name' => 'restored_with[]', 'type' => 'object', 'description' => 'One kind that came back.'],
                ['name' => 'restored_with[].kind', 'type' => 'string', 'description' => 'The kind of what came back.'],
                ['name' => 'restored_with[].count', 'type' => 'integer', 'description' => 'How many items of that kind came back.'],
            ], withLinks: false),
            'title' => 'Restore an item of the trash',
            'label' => 'Restore an item',
            'method' => 'PUT',
            'path' => '/vaults/{vault}/trash/{kind}/{item}/restoration',
            'examplePath' => '/vaults/'.$vaultSlug.'/trash/contact/'.$janId.'/restoration',
            'description' => 'Bring one item back where it was, with everything deleted at the same time as it. A person or a group gets back the reminders their deletion stopped.',
            'body' => [
                'An item deleted with a person or a group still in the trash answers 422 on item, saying to restore them first. So does a relationship or a membership whose other end is in the trash, and a record whose type was deleted from the account settings.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $itemIdentifier,
            'returns' => 'What came back.',
            'response' => [
                'data' => [
                    'type' => 'trash_item',
                    'id' => $janId,
                    'attributes' => [
                        'kind' => 'contact',
                        'title' => 'Jan Levinson',
                        'restored_with' => [['kind' => 'relationship', 'count' => 1]],
                    ],
                ],
            ],
        ],
    ],
];
