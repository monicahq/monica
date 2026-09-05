<?php

declare(strict_types=1);

return [

    'locales' => [
        'en' => [
            'label' => 'English',
            'region' => 'United Kingdom',
            'date_format' => 'd/m/Y',
            'number_format' => 'comma_dot',
            'flag' => 'linear-gradient(to bottom, transparent 40%, #C8102E 40% 60%, transparent 60%), linear-gradient(to right, transparent 38%, #C8102E 38% 62%, transparent 62%), linear-gradient(to bottom, transparent 30%, #fff 30% 70%, transparent 70%), linear-gradient(to right, transparent 28%, #fff 28% 72%, transparent 72%), linear-gradient(to bottom right, transparent 44%, #fff 44% 56%, transparent 56%), linear-gradient(to bottom left, transparent 44%, #fff 44% 56%, transparent 56%), #012169',
            'typography' => [
                'apostrophe' => "\u{2019}",
                'quotes' => [
                    'open' => '“',
                    'close' => '”',
                ],
            ],
        ],
        'fr_FR' => [
            'label' => 'Français',
            'region' => 'France',
            'date_format' => 'd/m/Y',
            'number_format' => 'space_comma',
            'flag' => 'linear-gradient(to right, #002395 0 33%, #fff 33% 66%, #ed2939 66%)',
            'typography' => [
                'apostrophe' => "\u{2019}",
                'space_before' => [
                    ':' => "\u{00A0}",
                    ';' => "\u{202F}",
                    '!' => "\u{202F}",
                    '?' => "\u{202F}",
                ],
                'quotes' => [
                    'open' => '«',
                    'close' => '»',
                    'inner_space' => "\u{202F}",
                ],
                'forbidden_words' => ['tu', 'toi', 'tes'],
            ],
        ],
        'de_DE' => [
            'label' => 'Deutsch',
            'region' => 'Deutschland',
            'date_format' => 'd.m.Y',
            'number_format' => 'dot_comma',
            'flag' => 'linear-gradient(to bottom, #000 0 33%, #dd0000 33% 66%, #ffce00 66%)',
            'typography' => [
                'apostrophe' => "\u{2019}",
                'quotes' => [
                    'open' => '„',
                    'close' => '“',
                ],
            ],
        ],
        'es_ES' => [
            'label' => 'Español',
            'region' => 'España',
            'date_format' => 'd/m/Y',
            'number_format' => 'dot_comma',
            'flag' => 'linear-gradient(to bottom, #aa151b 0 25%, #f1bf00 25% 75%, #aa151b 75%)',
            'typography' => [
                'quotes' => [
                    'open' => '«',
                    'close' => '»',
                ],
            ],
        ],
        'pt_PT' => [
            'label' => 'Português',
            'region' => 'Portugal',
            'date_format' => 'd/m/Y',
            'number_format' => 'space_comma',
            'flag' => 'linear-gradient(to right, #006600 0 40%, #ff0000 40%)',
            'typography' => [
                'quotes' => [
                    'open' => '«',
                    'close' => '»',
                ],
            ],
        ],
        'nl_NL' => [
            'label' => 'Nederlands',
            'region' => 'Nederland',
            'date_format' => 'd-m-Y',
            'number_format' => 'dot_comma',
            'typography' => [
                'apostrophe' => "\u{2019}",
                'quotes' => [
                    'open' => '“',
                    'close' => '”',
                ],
            ],
            'flag' => 'linear-gradient(to bottom, #ae1c28 0 33%, #fff 33% 66%, #21468b 66%)',
        ],
        'pt_BR' => [
            'label' => 'Português',
            'region' => 'Brasil',
            'date_format' => 'd/m/Y',
            'number_format' => 'dot_comma',
            'answers_bare_language' => true,
            'flag' => 'radial-gradient(circle at 50% 50%, #002776 0 28%, transparent 28%), linear-gradient(to bottom right, #009c3b 0 25%, transparent 25%), linear-gradient(to bottom left, #009c3b 0 25%, transparent 25%), linear-gradient(to top right, #009c3b 0 25%, transparent 25%), linear-gradient(to top left, #009c3b 0 25%, transparent 25%), #fedf00',
            'typography' => [
                'quotes' => [
                    'open' => '“',
                    'close' => '”',
                ],
            ],
        ],
        'it_IT' => [
            'label' => 'Italiano',
            'region' => 'Italia',
            'date_format' => 'd/m/Y',
            'number_format' => 'dot_comma',
            'flag' => 'linear-gradient(to right, #008c45 0 33%, #fff 33% 66%, #cd212a 66%)',
            'typography' => [
                'apostrophe' => "\u{2019}",
                'quotes' => [
                    'open' => '«',
                    'close' => '»',
                ],
            ],
        ],
        'tr_TR' => [
            'label' => 'Türkçe',
            'region' => 'Türkiye',
            'date_format' => 'd.m.Y',
            'number_format' => 'dot_comma',
            'flag' => 'radial-gradient(circle at 62% 50%, #fff 0 9%, transparent 9%), radial-gradient(circle at 46% 50%, #e30a17 0 31%, transparent 31%), radial-gradient(circle at 40% 50%, #fff 0 32%, transparent 32%), #e30a17',
            'typography' => [
                'apostrophe' => "\u{2019}",
                'quotes' => [
                    'open' => '“',
                    'close' => '”',
                ],
            ],
        ],
    ],

    'remember_duration_days' => env('MONICA_REMEMBER_DURATION_DAYS', 30),

    'magic_link_duration_minutes' => env('MONICA_MAGIC_LINK_DURATION_MINUTES', 5),

    'terms_url' => env('MONICA_TERMS_URL', 'https://monicahq.com/terms'),

    'privacy_url' => env('MONICA_PRIVACY_URL', 'https://monicahq.com/privacy'),

    'self_hosted' => env('MONICA_SELF_HOSTED', false),

    'free_storage_limit_in_bytes' => env('MONICA_FREE_STORAGE_LIMIT_IN_BYTES', 104857600),

    'orphaned_file_grace_period_in_days' => env('MONICA_ORPHANED_FILE_GRACE_PERIOD_IN_DAYS', 30),

    'reminder_generation_window_in_days' => env('MONICA_REMINDER_GENERATION_WINDOW_IN_DAYS', 90),

    'notification_maximum_attempts' => env('MONICA_NOTIFICATION_MAXIMUM_ATTEMPTS', 3),

    'max_file_size_in_kilobytes' => env('MONICA_MAX_FILE_SIZE_IN_KILOBYTES', 10240),

    'version' => env('MONICA_VERSION', '3.0.0'),

    'instance' => env('MONICA_INSTANCE', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),

    'api_default_items_per_page' => env('MONICA_API_DEFAULT_ITEMS_PER_PAGE', 25),

    'api_maximum_items_per_page' => env('MONICA_API_MAXIMUM_ITEMS_PER_PAGE', 100),

];
