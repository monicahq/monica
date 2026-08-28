<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$user = [
    'type' => 'user',
    'id' => '1',
    'attributes' => [
        'first_name' => 'Michael',
        'last_name' => 'Scott',
        'email' => 'michael.scott@dundermifflin.com',
        'email_verified' => true,
        'locale' => 'en',
        'timezone' => 'America/New_York',
        'time_format' => '24',
        'date_format' => null,
        'name_order' => 'given_first',
        'number_format' => null,
        'created_at' => 1756252800,
        'updated_at' => 1756339200,
    ],
    'links' => [
        'self' => $base.'/me',
    ],
];

return [
    'name' => 'Your profile',
    'sections' => [
        [
            'id' => 'me-show',
            'title' => 'Get your profile',
            'label' => 'Get your profile',
            'method' => 'GET',
            'path' => '/me',
            'description' => 'Retrieve the user the token belongs to. A token acts as one person, so there is nobody else to ask for here and no identifier in the URL.',
            'rateLimit' => 'Sixty requests per minute, per token.',
            'returns' => 'A user object. Credentials never appear in it: not the password, not the second factor, not the recovery codes.',
            'response' => ['data' => $user],
        ],
        [
            'id' => 'me-update',
            'title' => 'Update your profile',
            'label' => 'Update your profile',
            'method' => 'PUT',
            'path' => '/me',
            'description' => 'Update the name and the address of the user the token belongs to.',
            'body' => [
                'Changing the address unconfirms it, because nobody has proved yet that the new one reaches you. Preferences such as the locale, the time zone and the clock are not changed here.',
            ],
            'rateLimit' => 'Sixty requests per minute, per token.',
            'bodyParams' => [
                [
                    'name' => 'first_name',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The first name of the user. Maximum 255 characters.',
                    'example' => 'Michael',
                ],
                [
                    'name' => 'last_name',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The last name of the user. Maximum 255 characters.',
                    'example' => 'Scott',
                ],
                [
                    'name' => 'email',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The address the user signs in with. Lowercase, and held by nobody else.',
                    'example' => 'michael.scott@dundermifflin.com',
                ],
            ],
            'returns' => 'The updated user object.',
            'response' => ['data' => $user],
        ],
    ],
];
