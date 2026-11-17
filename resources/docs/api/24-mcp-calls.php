<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$read = [
    'type' => 'mcp_call',
    'id' => '9d3c6a52-4a8e-4f6b-9f1e-2b7c1d0e5a41',
    'attributes' => [
        'started_at_in_milliseconds' => 1792765927412,
        'duration_in_milliseconds' => 58,
        'connection' => [
            'type' => 'api_key',
            'id' => '14',
            'name' => 'Dundie awards bot',
        ],
        'tool' => 'get-contact',
        'operation' => 'read',
        'vault' => [
            'id' => '0f7e2c1a-6b3d-4e59-8a21-5c9d3b7e1f60',
            'name' => 'Dunder Mifflin',
        ],
        'subject' => [
            'type' => 'contact',
            'label' => 'Dwight Schrute',
        ],
        'result' => 'succeeded',
        'error' => null,
        'json_rpc_id' => '7',
        'ip_address' => '192.168.1.24',
        'parameters' => '{"vault":"dunder-mifflin","contact":"dwight-schrute"}',
        'parameters_truncated' => false,
        'response' => '{"content":[{"type":"text","text":"{\"name\":\"Dwight Schrute\"}"}],"isError":false}',
        'response_truncated' => false,
    ],
    'links' => [
        'self' => $base.'/mcp-calls/9d3c6a52-4a8e-4f6b-9f1e-2b7c1d0e5a41',
    ],
];

$invalid = [
    'type' => 'mcp_call',
    'id' => '5b1f0e9c-2d4a-4c7e-8b36-1a9e7d2c4f03',
    'attributes' => [
        'started_at_in_milliseconds' => 1792765991204,
        'duration_in_milliseconds' => 21,
        'connection' => [
            'type' => 'oauth_client',
            'id' => '9a1f4c2e-7b3d-4f8a-a6e1-3c5d9b2e7f14',
            'name' => 'Claude',
        ],
        'tool' => 'update-life-event',
        'operation' => 'update',
        'vault' => [
            'id' => '0f7e2c1a-6b3d-4e59-8a21-5c9d3b7e1f60',
            'name' => 'Dunder Mifflin',
        ],
        'subject' => [
            'type' => 'life_event',
            'label' => 'Dundies',
        ],
        'result' => 'invalid_parameters',
        'error' => [
            'code' => -32602,
            'message' => 'The happened on field must match the format Y-m-d.',
        ],
        'json_rpc_id' => '8',
        'ip_address' => '192.168.1.24',
        'parameters' => '{"vault":"dunder-mifflin","life_event":"7c2e9a14-3b5d-4f61-8e27-9d1a4c6b2f85","happened_on":"31/02/1978"}',
        'parameters_truncated' => false,
        'response' => '{"content":[{"type":"text","text":"The happened on field must match the format Y-m-d."}],"isError":true}',
        'response_truncated' => false,
    ],
    'links' => [
        'self' => $base.'/mcp-calls/5b1f0e9c-2d4a-4c7e-8b36-1a9e7d2c4f03',
    ],
];

$callAttributes = [
    ['name' => 'started_at_in_milliseconds', 'type' => 'integer', 'description' => 'When the call started, as a count of milliseconds since 1970. Milliseconds rather than seconds, because an assistant often makes several calls within the same second.'],
    ['name' => 'duration_in_milliseconds', 'type' => 'integer', 'description' => 'How long the tool took, or null while the call is interrupted.'],
    ['name' => 'connection', 'type' => 'object', 'description' => 'What the assistant connected with.'],
    ['name' => 'connection.type', 'type' => 'string', 'description' => 'api_key for an API key, oauth_client for an assistant connected through OAuth.'],
    ['name' => 'connection.id', 'type' => 'string', 'description' => 'The id of the key or of the OAuth client. It stays after the key is revoked or the assistant disconnected.'],
    ['name' => 'connection.name', 'type' => 'string', 'description' => 'The name the connection had when the call was made. It is the name you or the assistant chose, not a verified identity of whoever made the assistant.'],
    ['name' => 'tool', 'type' => 'string', 'description' => 'The name of the tool, as the assistant sent it, such as get-contact.'],
    ['name' => 'operation', 'type' => 'string', 'description' => 'read, create, update or delete, taken from the API endpoint the tool mirrors. Null for a tool that does not exist.'],
    ['name' => 'vault', 'type' => 'object', 'description' => 'The vault the call reached, or null when it named none inside your account.'],
    ['name' => 'vault.id', 'type' => 'uuid', 'description' => 'The id of the vault, or null once the vault is deleted.'],
    ['name' => 'vault.name', 'type' => 'string', 'description' => 'The name the vault had when the call was made.'],
    ['name' => 'subject', 'type' => 'object', 'description' => 'The record the call reached, or null when it reached none inside your account.'],
    ['name' => 'subject.type', 'type' => 'string', 'description' => 'What kind of record it is, such as contact or life_event.'],
    ['name' => 'subject.label', 'type' => 'string', 'description' => 'The name the record had when the call was made.'],
    ['name' => 'result', 'type' => 'string', 'description' => 'succeeded, refused, invalid_parameters, error or interrupted. It is read from what the tool answered, never from the HTTP status, which is 200 for a failed tool call too. A call that started and never finished stays interrupted.'],
    ['name' => 'error', 'type' => 'object', 'description' => 'Why the call did not succeed, or null when it did.'],
    ['name' => 'error.code', 'type' => 'integer', 'description' => 'The JSON-RPC code: -32602 when the parameters or the record were refused, -32603 for anything else.'],
    ['name' => 'error.message', 'type' => 'string', 'description' => 'The reason sent to the assistant, word for word.'],
    ['name' => 'json_rpc_id', 'type' => 'string', 'description' => 'The id the assistant gave the request, as a string.'],
    ['name' => 'ip_address', 'type' => 'string', 'description' => 'The address the request came from, up to 45 characters.'],
    ['name' => 'parameters', 'type' => 'string', 'description' => 'The parameters the assistant sent, as JSON, with secrets and the values of sensitive fields masked before they were stored.'],
    ['name' => 'parameters_truncated', 'type' => 'boolean', 'description' => 'Whether the parameters were longer than 16 384 bytes and only their beginning was kept, in which case parameters is not valid JSON.'],
    ['name' => 'response', 'type' => 'string', 'description' => 'The response the assistant received, as JSON, masked the same way, or null when the call ended in a JSON-RPC error.'],
    ['name' => 'response_truncated', 'type' => 'boolean', 'description' => 'Whether the response was cut the same way.'],
];

return [
    'name' => 'MCP history',
    'description' => 'Every call an assistant made to a tool of the MCP server on your account, including reads, refusals and failures, kept apart from the account log.',
    'sections' => [
        [
            'id' => 'mcp-calls-index',
            'returnFields' => ApiDocsFields::page('mcp_call', 'call', 'calls', $callAttributes),
            'title' => 'List MCP calls',
            'label' => 'List MCP calls',
            'method' => 'GET',
            'path' => '/mcp-calls',
            'description' => 'List the calls assistants made to your account through MCP, newest first.',
            'body' => [
                'The account log records what changed. This records what was asked: every tool call, whether it read, changed, was refused, was given wrong parameters or failed. A call made with no valid key is never attributed to an account, so it never appears here.',
                'Calls are kept for 90 days by default, a period the instance sets, and then removed. Nothing here can be written, changed or removed through the API.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'permissions' => 'Your own account only. While the MCP server is switched off for your account, the endpoint answers 404.',
            'queryParams' => [
                [
                    'name' => 'period',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'How far back to look: day for the last 24 hours, week for the last 7 days, month for the last 30 days, or all for everything still kept. Any other value answers 422.',
                    'default' => 'all',
                ],
                [
                    'name' => 'connection',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Only the calls of one connection, written as its type and its id joined by a colon, such as api_key:14 or oauth_client:9a1f4c2e-7b3d-4f8a-a6e1-3c5d9b2e7f14.',
                ],
                [
                    'name' => 'result',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Only the calls with one result: succeeded, refused, invalid_parameters, error or interrupted. Any other value answers 422.',
                ],
                [
                    'name' => 'tool',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Only the calls to one tool, by its name, such as get-contact.',
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
                    'description' => 'How many calls one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused.',
                    'default' => '25',
                ],
            ],
            'returns' => 'A page of calls, and how many there are in all.',
            'response' => [
                'data' => [$invalid, $read],
                'meta' => [
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 2,
                ],
            ],
        ],
        [
            'id' => 'mcp-calls-show',
            'returnFields' => ApiDocsFields::one('mcp_call', 'call', $callAttributes),
            'title' => 'Get an MCP call',
            'label' => 'Get an MCP call',
            'method' => 'GET',
            'path' => '/mcp-calls/{mcpCall}',
            'examplePath' => '/mcp-calls/5b1f0e9c-2d4a-4c7e-8b36-1a9e7d2c4f03',
            'description' => 'Retrieve one call an assistant made to your account through MCP.',
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'permissions' => 'Your own account only. While the MCP server is switched off for your account, the endpoint answers 404.',
            'pathParams' => [
                [
                    'name' => 'mcpCall',
                    'type' => 'uuid',
                    'required' => true,
                    'description' => 'The id of the call.',
                ],
            ],
            'returns' => 'A call. A call from another account answers 404.',
            'response' => ['data' => $invalid],
        ],
    ],
];
