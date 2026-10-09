<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Entity;
use App\Models\LifeEvent;

$searchableAttributes = ['name', 'body', 'sensitive_body', 'linked_names'];

$typesenseSchema = [
    'fields' => [
        ['name' => 'id', 'type' => 'string'],
        ['name' => 'account_id', 'type' => 'int64', 'facet' => true],
        ['name' => 'vault_id', 'type' => 'int64', 'facet' => true],
        ['name' => 'entity_type_id', 'type' => 'int64', 'facet' => true, 'optional' => true],
        ['name' => 'name', 'type' => 'string'],
        ['name' => 'body', 'type' => 'string'],
        ['name' => 'sensitive_body', 'type' => 'string'],
        ['name' => 'linked_names', 'type' => 'string'],
    ],
];

$algoliaSettings = [
    'searchableAttributes' => $searchableAttributes,
    'attributesForFaceting' => ['filterOnly(account_id)', 'filterOnly(vault_id)', 'filterOnly(entity_type_id)'],
    'ranking' => ['words', 'filters', 'attribute', 'typo', 'proximity', 'exact', 'custom'],
    'removeWordsIfNoResults' => 'none',
];

$meilisearchSettings = [
    'filterableAttributes' => ['account_id', 'vault_id', 'entity_type_id'],
    'searchableAttributes' => $searchableAttributes,
    'rankingRules' => ['words', 'attribute', 'typo', 'proximity', 'exactness'],
];

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search connection that gets used while
    | using Laravel Scout. This connection is used when syncing all models
    | to the search service. You should adjust this based on your needs.
    |
    | Search runs on "algolia", "meilisearch", "typesense" or "database".
    | The outside engines tolerate typing mistakes. The "database" engine
    | does not: it finds every word, or the start of one, in the columns a
    | record's document is written to. "null" keeps search unavailable.
    |
    */

    'driver' => env('SCOUT_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you have multiple
    | "tenants" or applications sharing the same search infrastructure.
    |
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing will get queued for better performance.
    | Saving a record never waits for the engine, so this stays on, on the
    | "default" queue.
    |
    */

    'queue' => env('SCOUT_QUEUE', true) ? [
        'connection' => env('SCOUT_QUEUE_CONNECTION'),
        'queue' => 'default',
    ] : false,

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    |
    | This configuration option determines if your data will only be synced
    | with your search indexes after every open database transaction has
    | been committed, thus preventing any discarded data from syncing.
    |
    */

    'after_commit' => true,

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options allow you to control the maximum chunk size when you are
    | mass importing data into the search engine. This allows you to fine
    | tune each of these chunk sizes based on the power of the servers.
    |
    */

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option allows to control whether to keep soft deleted records in
    | the search indexes. Maintaining soft deleted records can be useful
    | if your application still needs to search for the records later.
    |
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to notify the search engine
    | of the user performing the search. This is sometimes useful if the
    | engine supports any analytics based on this application's users.
    |
    | Supported engines: "algolia"
    |
    */

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Algolia settings. Algolia is a cloud hosted
    | search engine which works great with Scout out of the box. Just plug
    | in your application ID and admin API key to get started searching.
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
        'index-settings' => [
            Contact::class => $algoliaSettings,
            Entity::class => $algoliaSettings,
            LifeEvent::class => $algoliaSettings,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Meilisearch settings. Meilisearch is an open
    | source search engine with minimal configuration. Below, you can state
    | the host and key information for your own Meilisearch installation.
    |
    | See: https://www.meilisearch.com/docs/learn/configuration/instance_options#all-instance-options
    |
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            Contact::class => $meilisearchSettings,
            Entity::class => $meilisearchSettings,
            LifeEvent::class => $meilisearchSettings,
        ],
        'model-settings' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Typesense Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Typesense settings. Typesense is an open
    | source search engine using minimal configuration. Below, you will
    | state the host, key, and schema configuration for the instance.
    |
    */

    'typesense' => [
        'client-settings' => [
            'api_key' => env('TYPESENSE_API_KEY', 'xyz'),
            'nodes' => [
                [
                    'host' => env('TYPESENSE_HOST', 'localhost'),
                    'port' => env('TYPESENSE_PORT', '8108'),
                    'path' => env('TYPESENSE_PATH', ''),
                    'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
                ],
            ],
            'nearest_node' => [
                'host' => env('TYPESENSE_HOST', 'localhost'),
                'port' => env('TYPESENSE_PORT', '8108'),
                'path' => env('TYPESENSE_PATH', ''),
                'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
            ],
            'connection_timeout_seconds' => env('TYPESENSE_CONNECTION_TIMEOUT_SECONDS', 2),
            'healthcheck_interval_seconds' => env('TYPESENSE_HEALTHCHECK_INTERVAL_SECONDS', 30),
            'num_retries' => env('TYPESENSE_NUM_RETRIES', 3),
            'retry_interval_seconds' => env('TYPESENSE_RETRY_INTERVAL_SECONDS', 1),
        ],
        // 'max_total_results' => env('TYPESENSE_MAX_TOTAL_RESULTS', 1000),
        'model-settings' => [
            Contact::class => ['collection-schema' => $typesenseSchema, 'search-parameters' => ['query_by' => implode(',', $searchableAttributes)]],
            Entity::class => ['collection-schema' => $typesenseSchema, 'search-parameters' => ['query_by' => implode(',', $searchableAttributes)]],
            LifeEvent::class => ['collection-schema' => $typesenseSchema, 'search-parameters' => ['query_by' => implode(',', $searchableAttributes)]],
        ],
        'import_action' => env('TYPESENSE_IMPORT_ACTION', 'upsert'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Turbopuffer Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Turbopuffer connection and the schema and
    | searchable attributes defined by each of your application's models.
    | Turbopuffer is a scalable engine with full-text + vector search.
    |
    */

    'turbopuffer' => [
        'api_key' => env('TURBOPUFFER_API_KEY'),
        'region' => env('TURBOPUFFER_REGION', 'gcp-us-central1'),
        'base_url' => env('TURBOPUFFER_BASE_URL'),
        'timeout' => env('TURBOPUFFER_TIMEOUT', 60),
        'connect_timeout' => env('TURBOPUFFER_CONNECT_TIMEOUT', 5),
        'retries' => env('TURBOPUFFER_RETRIES', 3),
        'model-settings' => [
            // User::class => [
            //     'searchable-attributes' => [
            //         'name' => 2,
            //         'email' => 1,
            //     ],
            //     'embedding' => [
            //         'attribute' => 'embedding',
            //         'dimensions' => 1536,
            //     ],
            //     'schema' => [
            //         'name' => ['type' => 'string', 'full_text_search' => true],
            //         'email' => ['type' => 'string', 'full_text_search' => true],
            //         'embedding' => ['type' => '[1536]f32', 'ann' => true],
            //     ],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Create full text indexes
    |--------------------------------------------------------------------------
    |
    | The database driver searches the search_* columns of contacts, entities
    | and life events through a full text index. If you never intend to use the
    | database driver, you may deactivate those indexes. This option is only
    | read while migrating the database, so the database driver needs them to
    | have been created then.
    |
    | Full text indexes exist on MySQL, MariaDB and PostgreSQL only. SQLite has
    | none, and the database driver searches it with LIKE instead.
    |
    */

    'full_text_index' => (bool) env('FULL_TEXT_INDEX', true),

];
