<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

return [
    'name' => 'Getting started',
    'guide' => true,
    'sections' => [
        [
            'id' => 'introduction',
            'title' => 'Introduction',
            'label' => 'Introduction',
            'method' => 'GET',
            'path' => '/status',
            'auth' => false,
            'description' => 'The '.config('app.name').' API gives you programmatic access to your account from outside the browser. It is organised around REST, it answers JSON on every endpoint, and it is the same API the product itself is built to be reachable through.',
            'body' => [
                'There is no test mode. Every request is processed against your real account, so be careful with anything that deletes.',
                'The version is part of the URL rather than a header. A caller who writes /api/v1 today keeps working the day a second version arrives beside it.',
                'Everything on these pages is also published as an OpenAPI 3.2 document. Import it into Postman, Bruno, Swagger UI or anything else that reads OpenAPI and every endpoint arrives with its parameters, its answers and its refusals already described. Note that openapi-generator cannot read version 3.2 yet.',
                'That document is built from these pages when you ask for it, so it describes this instance and names it as its server, and it can neither describe an endpoint this instance does not answer nor miss one that it does. A returned value is described as admitting null, because these pages record what a field holds when it holds something and not whether it may hold nothing.',
            ],
            'rateLimit' => 'Sixty requests per minute, per address.',
            'response' => [
                'status' => 'ok',
                'version' => '3.0.0',
                'instance' => 'app.monicahq.com',
            ],
        ],
        [
            'id' => 'authentication-guide',
            'title' => 'Authentication',
            'label' => 'Authentication',
            'method' => 'GET',
            'path' => '/me',
            'description' => 'Every request outside the status endpoint carries a token, as a bearer token in the Authorization header. Get one by registering or logging in through the API, or make one by hand in security settings.',
            'body' => [
                'A key acts as you, with exactly the access you have. Make one per integration, so you can revoke it alone.',
                'Tokens do not expire. They are revoked rather than refreshed, either by logging out or from the security screen.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'response' => [
                'data' => [
                    'type' => 'user',
                    'id' => '1',
                    'attributes' => [
                        'first_name' => 'Michael',
                        'last_name' => 'Scott',
                        'email' => 'michael.scott@dundermifflin.com',
                    ],
                    'links' => [
                        'self' => $base.'/me',
                    ],
                ],
            ],
        ],
        [
            'id' => 'rate-limits',
            'title' => 'Rate limits',
            'label' => 'Rate limits',
            'method' => 'POST',
            'path' => '/login',
            'auth' => false,
            'description' => 'Most endpoints allow sixty requests per minute. Register and log in allow six, because they take a password and a looser limit would make them a way to guess one.',
            'body' => [
                'The sixty are counted per user, not per key. Every key you make, and every device syncing over CardDAV, draws on the same budget, so making another key does not raise it.',
                'Every answer carries the budget left in X-RateLimit-Remaining. A request over the limit answers 429 and says in Retry-After how many seconds to wait.',
            ],
            'rateLimit' => 'Six requests per minute, per address.',
            'responseStatus' => 429,
            'response' => [
                'message' => 'Too Many Attempts.',
            ],
        ],
        [
            'id' => 'pagination',
            'title' => 'Pagination',
            'label' => 'Pagination',
            'method' => 'GET',
            'path' => '/vaults',
            'description' => 'Endpoints that answer with a list are paginated, and they all page the same way. Pass page to move through the results and per_page to size them, between 1 and 100.',
            'body' => [
                'The meta object says which page this is, how many objects a page holds, and how many there are in total, so a caller knows when it has reached the last page.',
                'Two short lists answer whole, with no meta: the notification channels and the fields a life event type asks for.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'queryParams' => [
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
                    'description' => 'How many objects one page holds, between 1 and 100.',
                    'default' => '25',
                ],
            ],
            'response' => [
                'data' => [],
                'meta' => [
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 0,
                ],
            ],
        ],
        [
            'id' => 'errors',
            'title' => 'Errors',
            'label' => 'Errors',
            'method' => 'PUT',
            'path' => '/me',
            'description' => 'The API uses the ordinary HTTP status codes. 2xx means it worked, 4xx means the request was wrong, 5xx means this end was.',
            'body' => [
                'Every refusal answers JSON with a message saying what went wrong. A request that fails validation answers 422 and adds an errors object keyed by field name, naming every field at fault at once. The example on the right is one of those.',
                'The API never answers 403. Something you can reach but may not change in its current state answers 422 with the reason, and something you cannot reach answers 404.',
            ],
            'statusCodes' => [
                [
                    'status' => 200,
                    'name' => 'OK',
                    'when' => 'The request worked.',
                    'body' => 'The record, or the page of records, that was asked for.',
                ],
                [
                    'status' => 201,
                    'name' => 'Created',
                    'when' => 'Something was created: a record, a token, a merge, or a checkout or portal session.',
                    'body' => 'What was created.',
                ],
                [
                    'status' => 204,
                    'name' => 'No Content',
                    'when' => 'The request worked and there is nothing to send back, after a deletion or a logout for example.',
                    'body' => 'None.',
                ],
                [
                    'status' => 401,
                    'name' => 'Unauthorized',
                    'when' => 'No token was sent, or the one sent has been revoked. Log in answers it too when the email and password do not match, or when the two-factor code is wrong.',
                    'body' => 'A message.',
                ],
                [
                    'status' => 404,
                    'name' => 'Not Found',
                    'when' => 'Nothing answers to that path. A record belonging to another account, one reached through a vault that does not hold it, and an identifier that is not well formed all answer the same way, so that nothing is disclosed about them.',
                    'body' => 'A message.',
                ],
                [
                    'status' => 405,
                    'name' => 'Method Not Allowed',
                    'when' => 'The path exists, but not with that method.',
                    'body' => 'A message.',
                ],
                [
                    'status' => 409,
                    'name' => 'Conflict',
                    'when' => 'Merging two contacts, when either of them changed after the preview was read. Nothing is written. Read the preview again and send its new fingerprint.',
                    'body' => 'A message.',
                ],
                [
                    'status' => 413,
                    'name' => 'Content Too Large',
                    'when' => 'The request body is larger than this server accepts, which only an upload such as an avatar can reach. It is refused before validation runs.',
                    'body' => 'A message.',
                ],
                [
                    'status' => 422,
                    'name' => 'Unprocessable Content',
                    'when' => 'A field is missing or invalid, or the request cannot be carried out in the current state of the record, such as answering a reminder twice or cancelling a subscription that is already ending.',
                    'body' => 'A message, and an errors object listing every field at fault.',
                ],
                [
                    'status' => 429,
                    'name' => 'Too Many Requests',
                    'when' => 'The rate limit was reached. Retry-After says how many seconds to wait.',
                    'body' => 'A message.',
                ],
                [
                    'status' => 500,
                    'name' => 'Internal Server Error',
                    'when' => 'Something failed on this end rather than in the request.',
                    'body' => 'A message.',
                ],
                [
                    'status' => 503,
                    'name' => 'Service Unavailable',
                    'when' => 'The instance is down for maintenance. The status endpoint answers it when the database cannot be reached, and starting a checkout or a portal session answers it when Stripe did not answer.',
                    'body' => 'A message. The status endpoint answers a status of error instead.',
                ],
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'responseStatus' => 422,
            'response' => [
                'message' => 'The first name field is required.',
                'errors' => [
                    'first_name' => [
                        'The first name field is required.',
                    ],
                ],
            ],
        ],
    ],
];
