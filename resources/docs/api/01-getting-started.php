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
            'rateLimit' => 'Sixty requests per minute, per token.',
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
            'description' => 'Every endpoint that answers with a list is paginated, and they all page the same way. Pass page to move through the results and per_page to size them, between 1 and 100.',
            'body' => [
                'No endpoint in this version answers with a list yet. The convention is written down first so that the ones that arrive next all follow it, rather than each inventing its own.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
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
                'A request that fails validation answers 422, with a message and an errors object keyed by field name, naming every field at fault at once. A request with no token, or with one that has been revoked, answers 401. Anything belonging to another account answers 404, the same as something that never existed, because saying otherwise would confirm it exists.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
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
