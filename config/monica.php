<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | The languages the interface is translated into. The key is the locale, and
    | matches the name of the file in the lang folder. The flag is a css value
    | for the background property, drawn rather than shipped as an image. The
    | date format is the order that language reads dates in, and the number
    | format the separators it writes numbers with, both used for whoever has
    | not picked one of their own.
    |
    | The typography is how that language punctuates, read by monica:localize to
    | refuse a translation written any other way. It is optional: a locale that
    | declares none is not checked, which is where eight of the nine stand. The
    | rules themselves are written down in .claude/rules/translations.md. The
    | characters are escaped rather than pasted, because a non-breaking space is
    | invisible and an apostrophe has a twin that is wrong.
    |
    */

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
            /*
             * Which Portuguese answers a browser that says `pt` and names no
             * country. Only a language shipped in two regions needs this, and
             * Portuguese is the only one. Brazil rather than Portugal because
             * that is where most Portuguese is read and what CLDR resolves the
             * bare code to. Naming a country still wins: `pt-PT` reaches pt_PT.
             */
            'answers_bare_language' => true,
            /* Four green corners over yellow leave the lozenge, and the disc sits on top of it. */
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
            /* The crescent is a red disc laid over a white one, and the star is a dot: at 20 by 14 pixels a five-pointed star drawn in gradients reads as a smudge. */
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

    /*
    |--------------------------------------------------------------------------
    | Remember me
    |--------------------------------------------------------------------------
    |
    | How long a session survives when somebody ticks "remember me" on the sign
    | in screen. The screen names this number, so changing it here changes what
    | the visitor is promised.
    |
    */

    'remember_duration_days' => env('MONICA_REMEMBER_DURATION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Magic link
    |--------------------------------------------------------------------------
    |
    | How many minutes a passwordless sign in link stays valid. It is short on
    | purpose: the link is single use, but a short window limits the damage of
    | a forwarded or intercepted email.
    |
    */

    'magic_link_duration_minutes' => env('MONICA_MAGIC_LINK_DURATION_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Legal pages
    |--------------------------------------------------------------------------
    |
    | Where the terms of use and the privacy policy live. They sit on the public
    | site rather than in the application, so an instance that runs its own copy
    | of those documents can point at them here.
    |
    */

    'terms_url' => env('MONICA_TERMS_URL', 'https://monicahq.com/terms'),

    'privacy_url' => env('MONICA_PRIVACY_URL', 'https://monicahq.com/privacy'),

    /*
    |--------------------------------------------------------------------------
    | Self hosted
    |--------------------------------------------------------------------------
    |
    | Whether this instance runs outside of the cloud one. An account created on
    | a self hosted instance is never metered: it has no storage ceiling and no
    | rate limit applies to it.
    |
    */

    'self_hosted' => env('MONICA_SELF_HOSTED', false),

    /*
    |--------------------------------------------------------------------------
    | Free storage
    |--------------------------------------------------------------------------
    |
    | How many bytes of files an account gets on the free offer of the cloud
    | instance, before the one time purchase. The ceiling is stored on the
    | account when it is created, so raising this number here does not raise it
    | for the accounts that already exist.
    |
    */

    'free_storage_limit_in_bytes' => env('MONICA_FREE_STORAGE_LIMIT_IN_BYTES', 104857600),

    /*
    |--------------------------------------------------------------------------
    | Orphaned files
    |--------------------------------------------------------------------------
    |
    | How many days a file nothing points at is kept before the sweep takes it.
    | It has to be longer than the window in which a record can be restored to
    | an earlier version, or a restore recovers a pointer to nothing.
    |
    */

    'orphaned_file_grace_period_in_days' => env('MONICA_ORPHANED_FILE_GRACE_PERIOD_IN_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | The largest single file
    |--------------------------------------------------------------------------
    |
    | How many kilobytes one upload may weigh. The account's own storage ceiling
    | is the real limit and is enforced where the file is written; this is here
    | so a form can refuse an enormous file with a sentence under the control,
    | rather than letting PHP truncate it and reporting nothing at all.
    |
    | It has to stay under `upload_max_filesize` and `post_max_size` in the php
    | configuration, since neither of those can be argued with from here.
    |
    */

    'max_file_size_in_kilobytes' => env('MONICA_MAX_FILE_SIZE_IN_KILOBYTES', 10240),

    /*
    |--------------------------------------------------------------------------
    | What this instance is, and which release it runs
    |--------------------------------------------------------------------------
    |
    | The status endpoint of the API reports both, so that somebody pointing a
    | monitor at an instance can tell which one answered and what it was running
    | when it did. The version is the release rather than a commit: a self hosted
    | instance is a copy somebody chose to install, and the number they read here
    | is the number they compare against the changelog.
    |
    | The name is the host, and it defaults to the application URL rather than to
    | a fixed string, so an instance nobody configured still says something true
    | about itself.
    |
    */

    'version' => env('MONICA_VERSION', '3.0.0'),

    'instance' => env('MONICA_INSTANCE', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),

    /*
    |--------------------------------------------------------------------------
    | How many things one page of an API list holds
    |--------------------------------------------------------------------------
    |
    | Every list the API answers is paginated the same way, which the reference
    | promises under Getting started: `page` moves through the results and
    | `per_page` sizes them. The first value here is what a caller gets when it
    | asks for nothing, and the second is the ceiling: a request for more is
    | brought back down to it rather than refused, because a page size somebody
    | typed wrongly is not a reason to fail their call.
    |
    | It sits here rather than in the framework's own configuration because the
    | ceiling is an operator's decision. A hosted instance and a self hosted one
    | run the same product and differ in quotas, and this is one of them.
    |
    */

    'api_default_items_per_page' => env('MONICA_API_DEFAULT_ITEMS_PER_PAGE', 25),

    'api_maximum_items_per_page' => env('MONICA_API_MAXIMUM_ITEMS_PER_PAGE', 100),

];
