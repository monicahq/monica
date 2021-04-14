<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Acceptable password strength threshold
    |--------------------------------------------------------------------------
    |
    | The minimum password strength a user password is required to meet (0-4)
    | 0 - extremely guessable (within 10^3 guesses)
    | 1 is still very guessable (guesses < 10^6)
    | 2 is somewhat guessable (guesses < 10^8)
    | 3 is safely unguessable (guesses < 10^10)
    | 4 is very unguessable (guesses >= 10^10)
    */
    'password_strength_threshold' => env('ZXCVBN_PASSWORD_STRENGTH_THRESHOLD', 3),

    /*
    |--------------------------------------------------------------------------
    | Maps the feedback warning to a 'code' that can be used for localisation
    |--------------------------------------------------------------------------
    |
    | A pull request has been raised include consistent 'warning code' strings
    | in the response from the zxcvbn package. When this is released, this
    | mapping wont be required as the 'warning code' will be able to be directly
    | mapped to translations.
    |
    | https://github.com/bjeavons/zxcvbn-php/pull/55
    */
    'translations_map' => [
        'Dates are often easy to guess' => 'guessable_date',
        'This is a top-10 common password' => 'common_top_10',
        'This is a top-100 common password' => 'common_top_100',
        'This is a very common password' => 'common',
        'This is similar to a commonly used password' => 'common_similar',
        'A word by itself is easy to guess' => 'guessable_word',
        'Names and surnames by themselves are easy to guess' => 'guessable_name',
        'Common names and surnames are easy to guess' => 'guessable_names',
        'Repeats like "aaa" are easy to guess' => 'guessable_repeated_character',
        'Repeats like "abcabcabc" are only slightly harder to guess than "abc"' => 'guessable_repeated_string',
        'Sequences like abc or 6543 are easy to guess' => 'guessable_sequence',
        'Straight rows of keys are easy to guess' => 'spatial_row',
        'Short keyboard patterns are easy to guess' => 'spatial_pattern',
        'Recent years are easy to guess' => 'years',
    ],
];
