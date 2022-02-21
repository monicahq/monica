<?php

namespace App\Http\Controllers\Settings\Users\ViewHelpers;

use App\Models\User;
use App\Helpers\DateHelper;

class UserIndexViewHelper
{
    public static function data(User $loggedUser): array
    {
        $users = $loggedUser->account->users;

        $userCollection = $users->map(function ($user) use ($loggedUser) {
            return self::dtoUser($user, $loggedUser);
        });

        return [
            'users' => $userCollection,
            'url' => [
                'settings' => [
                    'index' => route('settings.index'),
                ],
                'users' => [
                    'create' => route('settings.user.create'),
                ],
            ],
        ];
    }

    public static function dtoUser(User $user, User $loggedUser): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'is_account_administrator' => $user->is_account_administrator,
            'invitation_code' => $user->invitation_code ? $user->invitation_code : null,
            'invitation_accepted_at' => $user->invitation_accepted_at ? DateHelper::formatDate($user->invitation_accepted_at) : null,
            'is_logged_user' => $user->id === $loggedUser->id,
            'url' => [
                'show' => route('settings.user.show', [
                    'user' => $user,
                ]),
                'update' => route('settings.user.update', [
                    'user' => $user,
                ]),
                'destroy' => route('settings.user.destroy', [
                    'user' => $user,
                ]),
            ],
        ];
    }
}
