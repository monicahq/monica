<?php

declare(strict_types=1);

return [
    'name' => 'Authentication',
    'description' => 'Open a session, close one, and register an account. These are the only endpoints that need no token.',
    'sections' => [
        [
            'id' => 'register-store',
            'returnFields' => [
                ['name' => 'token', 'type' => 'string', 'description' => 'The bearer token. Send it as an Authorization header reading Bearer followed by this value, on every other endpoint. This is the only time it exists in readable form: only a hash of it is kept, so a token you fail to copy has to be made again.'],
                ['name' => 'expires_at', 'type' => 'timestamp', 'description' => 'When the token stops working, as a count of seconds, or null when it never does. Every token issued today is null: expiry is not something this API sets yet, and a token is ended by revoking it.'],
            ],
            'title' => 'Register',
            'label' => 'Register',
            'method' => 'POST',
            'path' => '/register',
            'auth' => false,
            'description' => 'Open an account from outside the browser, and get a token for it.',
            'body' => [
                'The account this builds is the account the sign up screen builds, down to the first vault and the lists it starts with. The terms are asked for here too, because a second way in that skipped them would be a way around them.',
            ],
            'rateLimit' => 'Six requests per minute, per address.',
            'bodyParams' => [
                [
                    'name' => 'first_name',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The first name of the person opening the account. Maximum 255 characters.',
                    'example' => 'Michael',
                ],
                [
                    'name' => 'last_name',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The last name of the person opening the account. Maximum 255 characters.',
                    'example' => 'Scott',
                ],
                [
                    'name' => 'email',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The address to sign in with. Lowercase, held by nobody else, and not from a throwaway provider.',
                    'example' => 'michael.scott@dundermifflin.com',
                ],
                [
                    'name' => 'password',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'At least 8 characters. Only a hash of it is ever written down.',
                    'example' => 'a-very-strong-password',
                ],
                [
                    'name' => 'password_confirmation',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The password again, exactly as above.',
                    'example' => 'a-very-strong-password',
                ],
                [
                    'name' => 'terms',
                    'type' => 'boolean',
                    'required' => true,
                    'description' => 'Agreement with the terms of use and the privacy policy. An account cannot be opened without it.',
                    'example' => true,
                ],
                [
                    'name' => 'timezone',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'An IANA time zone, which every date is then rendered in. Defaults to UTC.',
                ],
                [
                    'name' => 'device_name',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'What the token is for, in a few words. It names the key in security settings, so each integration can be revoked on its own.',
                    'example' => 'Dundie awards bot',
                ],
            ],
            'returns' => 'A token, in plain text. This is the only time it exists in that form: only a hash of it is kept, so a token you fail to copy has to be made again.',
            'responseStatus' => 201,
            'response' => [
                'token' => '1|f9aB2cD3eF4gH5iJ6kL7mN8oP9qR0sT1uV2w',
                'expires_at' => null,
            ],
        ],
        [
            'id' => 'login-store',
            'returnFields' => [
                ['name' => 'token', 'type' => 'string', 'description' => 'The bearer token. Send it as an Authorization header reading Bearer followed by this value, on every other endpoint. This is the only time it exists in readable form: only a hash of it is kept, so a token you fail to copy has to be made again.'],
                ['name' => 'expires_at', 'type' => 'timestamp', 'description' => 'When the token stops working, as a count of seconds, or null when it never does. Every token issued today is null: expiry is not something this API sets yet, and a token is ended by revoking it.'],
            ],
            'title' => 'Log in',
            'label' => 'Log in',
            'method' => 'POST',
            'path' => '/login',
            'auth' => false,
            'description' => 'Exchange an address and a password for a token. The token is named after device_name, so you can tell your integrations apart later and revoke one without touching the rest.',
            'body' => [
                'A user who has turned on two factor authentication must send a valid code as well, from their authenticator app or from their recovery codes. No token is issued until it passes, because an endpoint that skipped it would be a way around the second factor rather than another way in.',
                'Wrong password, suspended account, an account that signs in through an external provider, and an address nobody holds all answer the same way. Anything else would tell somebody which addresses have accounts here.',
            ],
            'rateLimit' => 'Six requests per minute, per address.',
            'bodyParams' => [
                [
                    'name' => 'email',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The address the account signs in with.',
                    'example' => 'michael.scott@dundermifflin.com',
                ],
                [
                    'name' => 'password',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The password of the account.',
                    'example' => 'a-very-strong-password',
                ],
                [
                    'name' => 'code',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'A code from the authenticator app, or one of the recovery codes. Required when the user has two factor authentication turned on. A recovery code is spent the moment it works.',
                ],
                [
                    'name' => 'device_name',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'What the token is for, in a few words. Shown in security settings.',
                    'example' => 'Dundie awards bot',
                ],
            ],
            'returns' => 'A token, in plain text, or a 401 status when anything at all was wrong with the credentials.',
            'response' => [
                'token' => '1|f9aB2cD3eF4gH5iJ6kL7mN8oP9qR0sT1uV2w',
                'expires_at' => null,
            ],
        ],
        [
            'id' => 'logout-destroy',
            'title' => 'Log out',
            'label' => 'Log out',
            'method' => 'DELETE',
            'path' => '/logout',
            'description' => 'Revokes the token you are calling with. Anything still using it stops working, immediately.',
            'body' => [
                'Your other tokens are untouched, which is the point of naming them: an integration you no longer trust can be cut off without signing everything else out.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'returns' => 'Nothing at all.',
            'responseStatus' => 204,
        ],
    ],
];
