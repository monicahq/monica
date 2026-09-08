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
        'pl_PL' => [
            'label' => 'Polski',
            'region' => 'Polska',
            'date_format' => 'd.m.Y',
            'number_format' => 'space_comma',
            'flag' => 'linear-gradient(to bottom, #fff 0 50%, #dc143c 50%)',
        ],
        'zh_CN' => [
            'label' => '简体中文',
            'region' => '中国',
            'date_format' => 'Y-m-d',
            'number_format' => 'comma_dot',
            'flag' => 'radial-gradient(circle at 20% 32%, #ffde00 0 22%, transparent 22%), radial-gradient(circle at 40% 15%, #ffde00 0 7%, transparent 7%), radial-gradient(circle at 49% 30%, #ffde00 0 7%, transparent 7%), radial-gradient(circle at 46% 49%, #ffde00 0 7%, transparent 7%), radial-gradient(circle at 35% 61%, #ffde00 0 7%, transparent 7%), #de2910',
        ],
        'ja_JP' => [
            'label' => '日本語',
            'region' => '日本',
            'date_format' => 'Y/m/d',
            'number_format' => 'comma_dot',
            'flag' => 'radial-gradient(circle at 50% 50%, #bc002d 0 35%, #fff 35%)',
        ],
        'ta_IN' => [
            'label' => 'தமிழ்',
            'region' => 'இந்தியா',
            'date_format' => 'd/m/Y',
            'number_format' => 'comma_dot',
            'flag' => 'radial-gradient(circle at 50% 50%, transparent 0 5%, #000080 5% 7%, transparent 7%), linear-gradient(to bottom, #ff9933 0 33.33%, #fff 33.33% 66.66%, #138808 66.66%)',
        ],
        'bn_BD' => [
            'label' => 'বাংলা',
            'region' => 'বাংলাদেশ',
            'date_format' => 'd/m/Y',
            'number_format' => 'comma_dot',
            'flag' => 'radial-gradient(circle at 45% 50%, #f42a41 0 24%, transparent 24%), linear-gradient(to bottom, #006a4e 0 100%)',
        ],
        'si_LK' => [
            'label' => 'සිංහල',
            'region' => 'ශ්‍රී ලංකාව',
            'date_format' => 'Y-m-d',
            'number_format' => 'comma_dot',
            'flag' => 'linear-gradient(to right, #ffbe29 0 6%, #00534e 6% 20%, #eb7400 20% 34%, #ffbe29 34% 40%, #8d2029 40%)',
        ],
        'uz_UZ' => [
            'label' => 'Oʻzbekcha',
            'region' => 'Oʻzbekiston',
            'date_format' => 'd.m.Y',
            'number_format' => 'space_comma',
            'flag' => 'linear-gradient(to bottom, #0099b5 0 30%, #ce1126 30% 33%, #fff 33% 67%, #ce1126 67% 70%, #1eb53a 70%)',
        ],
        'hi_IN' => [
            'label' => 'हिन्दी',
            'region' => 'भारत',
            'date_format' => 'd/m/Y',
            'number_format' => 'comma_dot',
            'flag' => 'radial-gradient(circle at 50% 50%, transparent 0 5%, #000080 5% 7%, transparent 7%), linear-gradient(to bottom, #ff9933 0 33.33%, #fff 33.33% 66.66%, #138808 66.66%)',
        ],
        'fil_PH' => [
            'label' => 'Filipino',
            'region' => 'Pilipinas',
            'date_format' => 'm/d/Y',
            'number_format' => 'comma_dot',
            'flag' => 'radial-gradient(circle at 13% 50%, #fcd116 0 9%, transparent 9%), linear-gradient(to bottom right, #fff 0 40%, transparent 40%) 0 0 / 100% 50% no-repeat, linear-gradient(to top right, #fff 0 40%, transparent 40%) 0 100% / 100% 50% no-repeat, linear-gradient(to bottom, #0038a8 0 50%, #ce1126 50%)',
        ],
        'ga_IE' => [
            'label' => 'Gaeilge',
            'region' => 'Éire',
            'date_format' => 'd/m/Y',
            'number_format' => 'comma_dot',
            'flag' => 'linear-gradient(to right, #169b62 0 33%, #fff 33% 66%, #ff883e 66%)',
        ],
        'vi_VN' => [
            'label' => 'Tiếng Việt',
            'region' => 'Việt Nam',
            'date_format' => 'd/m/Y',
            'number_format' => 'dot_comma',
            'flag' => 'conic-gradient(from 180deg, transparent 0 18deg, #ffff00 18deg 54deg, transparent 54deg 90deg, #ffff00 90deg 126deg, transparent 126deg 162deg, #ffff00 162deg 198deg, transparent 198deg 234deg, #ffff00 234deg 270deg, transparent 270deg 306deg, #ffff00 306deg 342deg, transparent 342deg) 50% 50% / 46% 62% no-repeat, #da251d',
        ],
        'th_TH' => [
            'label' => 'ไทย',
            'region' => 'ประเทศไทย',
            'date_format' => 'd/m/Y',
            'number_format' => 'comma_dot',
            'flag' => 'linear-gradient(to bottom, #a51931 0 16.66%, #f4f5f8 16.66% 33.33%, #2d2a4a 33.33% 66.66%, #f4f5f8 66.66% 83.33%, #a51931 83.33%)',
        ],
        'el_GR' => [
            'label' => 'Ελληνικά',
            'region' => 'Ελλάδα',
            'date_format' => 'd/m/Y',
            'number_format' => 'dot_comma',
            'flag' => 'linear-gradient(to bottom, #0d5eaf 0 11.11%, #fff 11.11% 22.22%, #0d5eaf 22.22% 33.33%, #fff 33.33% 44.44%, #0d5eaf 44.44% 55.55%, #fff 55.55% 66.66%, #0d5eaf 66.66% 77.77%, #fff 77.77% 88.88%, #0d5eaf 88.88%), linear-gradient(to right, #0d5eaf 0 40%, transparent 40%) 0 0 / 100% 55.55% no-repeat, linear-gradient(to bottom, transparent 11.11%, #fff 11.11% 22.22%, transparent 22.22% 33.33%, #fff 33.33% 44.44%, transparent 44.44%) 0 0 / 40% 55.55% no-repeat, linear-gradient(to right, transparent 16%, #fff 16% 24%, transparent 24%) 0 0 / 40% 55.55% no-repeat',
        ],
        'ca_ES' => [
            'label' => 'Català',
            'region' => 'Catalunya',
            'date_format' => 'd/m/Y',
            'number_format' => 'dot_comma',
            'flag' => 'linear-gradient(to bottom, #fcdd09 0 11.11%, #da121a 11.11% 22.22%, #fcdd09 22.22% 33.33%, #da121a 33.33% 44.44%, #fcdd09 44.44% 55.55%, #da121a 55.55% 66.66%, #fcdd09 66.66% 77.77%, #da121a 77.77% 88.88%, #fcdd09 88.88%)',
        ],
        'lt_LT' => [
            'label' => 'Lietuvių',
            'region' => 'Lietuva',
            'date_format' => 'Y-m-d',
            'number_format' => 'space_comma',
            'flag' => 'linear-gradient(to bottom, #fdb913 0 33%, #006a44 33% 66%, #c1272d 66%)',
        ],
        'fi_FI' => [
            'label' => 'Suomi',
            'region' => 'Suomi',
            'date_format' => 'j.n.Y',
            'number_format' => 'space_comma',
            'flag' => 'linear-gradient(to bottom, transparent 40%, #002f6c 40% 60%, transparent 60%), linear-gradient(to right, transparent 31%, #002f6c 31% 44%, transparent 44%), #fff',
        ],
        'is_IS' => [
            'label' => 'Íslenska',
            'region' => 'Ísland',
            'date_format' => 'j.n.Y',
            'number_format' => 'dot_comma',
            'flag' => 'linear-gradient(to bottom, transparent 43%, #d72828 43% 57%, transparent 57%), linear-gradient(to right, transparent 33.5%, #d72828 33.5% 41.5%, transparent 41.5%), linear-gradient(to bottom, transparent 36%, #fff 36% 64%, transparent 64%), linear-gradient(to right, transparent 28%, #fff 28% 47%, transparent 47%), #02529c',
        ],
        'hu_HU' => [
            'label' => 'Magyar',
            'region' => 'Magyarország',
            'date_format' => 'Y. m. d.',
            'number_format' => 'space_comma',
            'flag' => 'linear-gradient(to bottom, #ce2939 0 33%, #fff 33% 66%, #477050 66%)',
        ],
        'nb_NO' => [
            'label' => 'Norsk bokmål',
            'region' => 'Norge',
            'date_format' => 'd.m.Y',
            'number_format' => 'space_comma',
            'flag' => 'linear-gradient(to bottom, transparent 43%, #00205b 43% 57%, transparent 57%), linear-gradient(to right, transparent 33.5%, #00205b 33.5% 41.5%, transparent 41.5%), linear-gradient(to bottom, transparent 36%, #fff 36% 64%, transparent 64%), linear-gradient(to right, transparent 28%, #fff 28% 47%, transparent 47%), #ba0c2f',
        ],
        'da_DK' => [
            'label' => 'Dansk',
            'region' => 'Danmark',
            'date_format' => 'd-m-Y',
            'number_format' => 'dot_comma',
            'flag' => 'linear-gradient(to bottom, transparent 40%, #fff 40% 60%, transparent 60%), linear-gradient(to right, transparent 31%, #fff 31% 44%, transparent 44%), #c8102e',
        ],
        'sv_SE' => [
            'label' => 'Svenska',
            'region' => 'Sverige',
            'date_format' => 'Y-m-d',
            'number_format' => 'space_comma',
            'flag' => 'linear-gradient(to bottom, transparent 40%, #fecc00 40% 60%, transparent 60%), linear-gradient(to right, transparent 31%, #fecc00 31% 44%, transparent 44%), #006aa7',
        ],
        'ko_KR' => [
            'label' => '한국어',
            'region' => '대한민국',
            'date_format' => 'Y. m. d.',
            'number_format' => 'comma_dot',
            'flag' => 'linear-gradient(to bottom right, transparent 7%, #000 7% 9.5%, transparent 9.5% 11.5%, #000 11.5% 14%, transparent 14% 16%, #000 16% 18.5%, transparent 18.5% 81.5%, #000 81.5% 84%, transparent 84% 86%, #000 86% 88.5%, transparent 88.5% 90.5%, #000 90.5% 93%, transparent 93%), linear-gradient(to bottom left, transparent 7%, #000 7% 9.5%, transparent 9.5% 11.5%, #000 11.5% 14%, transparent 14% 16%, #000 16% 18.5%, transparent 18.5% 81.5%, #000 81.5% 84%, transparent 84% 86%, #000 86% 88.5%, transparent 88.5% 90.5%, #000 90.5% 93%, transparent 93%), radial-gradient(circle at 50% 50%, transparent 0 27%, #fff 27%), linear-gradient(to bottom right, #cd2e3a 0 50%, #0047a0 50%)',
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

    /*
     * A signed link that signs somebody in, so a screenshot tool that can only open a URL can reach
     * a screen behind the sign in.
     *
     * Three things have to be true at once for it to work: the environment is a development one,
     * this setting has been switched on by hand, and the instance answers on a development host.
     * The host is asked for because `.env.example` ships `APP_ENV=local`, so an instance deployed
     * from it would otherwise be one forgotten setting away from a way in. Above all that the link
     * is signed with the application key and expires, so minting one already takes everything an
     * attacker would need anyway.
     */
    'screenshot_link' => [
        'enabled' => (bool) env('MONICA_SCREENSHOT_LINK_ENABLED', false),
        'lifetime_in_minutes' => (int) env('MONICA_SCREENSHOT_LINK_LIFETIME_IN_MINUTES', 5),
        'allowed_hosts' => [
            'localhost',
            '127.0.0.1',
            '[::1]',
            '*.test',
        ],
    ],

];
