<?php

use App\Models\Contact;
use App\Models\Group;
use App\Models\Note;

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
    | Supported: "algolia", "meilisearch", "typesense",
    |            "database", "collection", "null"
    |
    */

    'driver' => env('SCOUT_DRIVER', 'algolia'),

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
    |
    */

    'queue' => env('SCOUT_QUEUE', false),

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

    'after_commit' => false,

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
        'host' => env('MEILISEARCH_URL', env('MEILISEARCH_HOST', 'http://localhost:7700')),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            Contact::class => [
                'filterableAttributes' => ['id', 'vault_id'],
                'sortableAttributes' => ['updated_at', 'first_name', 'last_name'],
            ],
            Group::class => [
                'filterableAttributes' => ['id', 'vault_id'],
                'sortableAttributes' => ['updated_at'],
            ],
            Note::class => [
                'filterableAttributes' => ['id', 'vault_id', 'contact_id'],
                'sortableAttributes' => ['updated_at'],
            ],
        ],
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
            'api_key' => env('TYPESENSE_API_KEY'),
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
        'model-settings' => [
            Contact::class => [
                'collection-schema' => [
                    'fields' => [
                        [
                            'name' => 'id',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'vault_id',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'first_name',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'last_name',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'middle_name',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'nickname',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'maiden_name',
                            'type' => 'string',
                        ],
                        [
                            'name' => '__soft_deleted',
                            'type' => 'int32',
                            'optional' => true,
                        ],
                        [
                            'name' => 'updated_at',
                            'type' => 'int32',
                        ],
                    ],
                    'default_sorting_field' => 'updated_at',
                ],
                'search-parameters' => [
                    'query_by' => 'first_name,last_name,middle_name,nickname,maiden_name',
                ],
            ],
            Group::class => [
                'collection-schema' => [
                    'fields' => [
                        [
                            'name' => 'id',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'vault_id',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'name',
                            'type' => 'string',
                        ],
                        [
                            'name' => '__soft_deleted',
                            'type' => 'int32',
                            'optional' => true,
                        ],
                        [
                            'name' => 'updated_at',
                            'type' => 'int32',
                        ],
                    ],
                    'default_sorting_field' => 'updated_at',
                ],
                'search-parameters' => [
                    'query_by' => 'name',
                ],
            ],
            Note::class => [
                'collection-schema' => [
                    'fields' => [
                        [
                            'name' => 'id',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'vault_id',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'contact_id',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'title',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'body',
                            'type' => 'string',
                        ],
                        [
                            'name' => 'updated_at',
                            'type' => 'int32',
                        ],
                    ],
                    'default_sorting_field' => 'updated_at',
                ],
                'search-parameters' => [
                    'query_by' => 'title,body',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Create full text indexes
    |--------------------------------------------------------------------------
    |
    | The database driver requires full text indexes on some columns. If you never
    | intend to use the database driver, you may want to deactivate those indexes.
    | This property is only used during migration of the database.
    | Be aware that you won't be able to switch back to the database driver if you
    | deactivate the full text indexes.
    |
    | Note: Full text indexes are only available on mysql and postgresql databases.
    |
    */

    'full_text_index' => (bool) env('FULL_TEXT_INDEX', true),
];
