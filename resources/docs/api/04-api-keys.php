<?php

declare(strict_types=1);

use App\Helpers\SupportAvailability;
use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$apiKey = [
    'type' => 'api_key',
    'id' => '3',
    'attributes' => [
        'name' => 'Dundie awards bot',
        'client' => 'assistant',
        'sensitive_fields' => false,
        'administration' => false,
        'last_used_at' => 1756339200,
        'expires_at' => null,
        'created_at' => 1756252800,
        'updated_at' => 1756252800,
    ],
    'links' => [
        'self' => $base.'/api-keys/3',
    ],
];

$olderApiKey = [
    'type' => 'api_key',
    'id' => '1',
    'attributes' => [
        'name' => 'Deployment script',
        'client' => 'script',
        'sensitive_fields' => false,
        'administration' => false,
        'last_used_at' => null,
        'expires_at' => null,
        'created_at' => 1756166400,
        'updated_at' => 1756166400,
    ],
    'links' => [
        'self' => $base.'/api-keys/1',
    ],
];

$pageParams = [
    [
        'name' => 'page',
        'type' => 'integer',
        'required' => false,
        'description' => 'Which page to answer with.',
        'default' => '1',
        'example' => 2,
    ],
    [
        'name' => 'per_page',
        'type' => 'integer',
        'required' => false,
        'description' => 'How many keys one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.',
        'default' => '25',
        'example' => 10,
    ],
];

$identifier = [
    [
        'name' => 'apiKey',
        'type' => 'integer',
        'required' => true,
        'description' => 'The id of the key.',
    ],
];

$keyAttributes = [
    ['name' => 'name', 'type' => 'string', 'description' => 'What the key is for, between 3 and 255 characters, as the person who minted it wrote it. Two keys may share a name; the id is what tells them apart.'],
    ['name' => 'client', 'type' => 'string', 'description' => 'What the key was minted for: script for something you wrote yourself, assistant for an assistant connecting over MCP. It tells the two apart on the security screen and in your account log, and it restricts nothing: a key of either kind reaches exactly what you reach.'],
    ['name' => 'sensitive_fields', 'type' => 'boolean', 'description' => 'Whether the key reaches the values of fields marked sensitive. False unless that was chosen when the key was minted, because a key is handed to a device and an address book on a phone can be read by the other applications on it.'],
    ...(SupportAvailability::isEnabled() ? [['name' => 'administration', 'type' => 'boolean', 'description' => 'Whether the key reaches the support queue of the whole instance. Only an administrator of the instance can mint such a key, and only when it was chosen at that moment.']] : []),
    ['name' => 'last_used_at', 'type' => 'timestamp', 'description' => 'When the key last let something in, as a count of seconds, or null if it never has. A key with null here has been minted and never used.'],
    ['name' => 'expires_at', 'type' => 'timestamp', 'description' => 'When the key stops working, as a count of seconds, or null when it never does. Every key issued today is null: expiry is not something this API sets yet.'],
    ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'When the key was minted, as a count of seconds since 1970.'],
    ['name' => 'updated_at', 'type' => 'timestamp', 'description' => 'When the row last changed, as a count of seconds, or null. Using a key changes it, so in practice it tracks last_used_at.'],
];

return [
    'name' => 'API keys',
    'description' => 'The tokens that reach this API. Create one, look at what you hold, and revoke what you no longer use.',
    'sections' => [
        [
            'id' => 'api-keys-index',
            'returnFields' => ApiDocsFields::page('api_key', 'key', 'keys', $keyAttributes),
            'title' => 'List keys',
            'label' => 'List keys',
            'method' => 'GET',
            'path' => '/api-keys',
            'description' => 'List the keys you have minted, newest first, including the one you are calling with.',
            'body' => [
                'A key acts as one person, so what comes back is yours and nobody else\'s. The key itself is not in here, in any form: it is readable once, in the response that created it, and only a hash of it is kept.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'queryParams' => $pageParams,
            'returns' => 'A page of key objects, and how many there are in all.',
            'response' => [
                'data' => [$apiKey, $olderApiKey],
                'meta' => [
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 2,
                ],
            ],
        ],
        [
            'id' => 'api-keys-show',
            'returnFields' => ApiDocsFields::one('api_key', 'key', $keyAttributes),
            'title' => 'Get a key',
            'label' => 'Get a key',
            'method' => 'GET',
            'path' => '/api-keys/{apiKey}',
            'examplePath' => '/api-keys/3',
            'description' => 'Retrieve one of your keys: what you called it, when you made it, and when it last let something in.',
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'A key object. A key belonging to somebody else answers 404, which is also what a key that never existed answers.',
            'response' => ['data' => $apiKey],
        ],
        [
            'id' => 'api-keys-create',
            'returnFields' => [
                ...ApiDocsFields::one('api_key', 'key', $keyAttributes),
                ['name' => 'token', 'type' => 'string', 'description' => 'The readable key, at the top of the answer rather than inside the object, because the shape describing a key is one that can never carry a secret. This is the only time it exists in this form: only a hash of it is kept, so a key you fail to copy has to be replaced rather than recovered.'],
            ],
            'title' => 'Create a key',
            'label' => 'Create a key',
            'method' => 'POST',
            'path' => '/api-keys',
            'description' => 'Mint a key, which acts as you and nobody else.',
            'body' => [
                'The readable value comes back in the token field, beside the object describing the key. This is the only time it exists in that form. Copy it now: it is not shown again, and a key you fail to copy is replaced rather than recovered.',
                'Calling this with a key is deliberate, and it is how a key is rotated without opening the application. Every creation is written to your account log, so a key that mints other keys is visible afterwards.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'bodyParams' => [
                [
                    'name' => 'name',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'What the key is for, in a few words. Between 3 and 255 characters. Two keys may share a name; the id is what tells them apart.',
                    'example' => 'Dundie awards bot',
                ],
                [
                    'name' => 'client',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'What will use the key: script, which is what it is when left out, or assistant for an assistant connecting over MCP. It describes the key and restricts nothing.',
                    'example' => 'assistant',
                ],
                [
                    'name' => 'sensitive_fields',
                    'type' => 'boolean',
                    'required' => false,
                    'description' => 'Whether this key may read the values of fields marked sensitive. False when it is left out. Turn it on only for a device that needs them. A key can only give another key a scope it holds itself, so a key without this scope gets 422 on sensitive_fields.',
                    'example' => false,
                ],
                ...(SupportAvailability::isEnabled() ? [[
                    'name' => 'administration',
                    'type' => 'boolean',
                    'required' => false,
                    'description' => 'Whether this key may work through the support queue of the whole instance. False when it is left out. Only an administrator of the instance may turn it on, and only with a key that holds the administration scope itself; anybody else gets 422 on administration.',
                    'example' => false,
                ]] : []),
            ],
            'returns' => 'The new key object, and the readable key beside it.',
            'responseStatus' => 201,
            'response' => [
                'data' => [
                    'type' => 'api_key',
                    'id' => '4',
                    'attributes' => [
                        'name' => 'Dundie awards bot',
                        'client' => 'assistant',
                        'sensitive_fields' => false,
                        'administration' => false,
                        'last_used_at' => null,
                        'expires_at' => null,
                        'created_at' => 1756425600,
                        'updated_at' => 1756425600,
                    ],
                    'links' => [
                        'self' => $base.'/api-keys/4',
                    ],
                ],
                'token' => '4|f9aB2cD3eF4gH5iJ6kL7mN8oP9qR0sT1uV2w',
            ],
        ],
        [
            'id' => 'api-keys-destroy',
            'title' => 'Delete a key',
            'label' => 'Delete a key',
            'method' => 'DELETE',
            'path' => '/api-keys/{apiKey}',
            'examplePath' => '/api-keys/3',
            'description' => 'Revoke a key, which stops working the moment this answers.',
            'body' => [
                'Anything still using it stops working, immediately, with nothing to wait out. Your other keys are untouched, which is the point of naming them: an integration you no longer trust can be cut off without breaking the rest.',
                'You may revoke the key you are calling with. This call succeeds and every later call with it answers 401.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => $identifier,
            'returns' => 'Nothing at all. A key belonging to somebody else answers 404, and keeps working.',
            'responseStatus' => 204,
        ],
    ],
];
