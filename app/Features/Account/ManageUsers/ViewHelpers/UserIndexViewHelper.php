<?php

namespace App\Services\Vault\ManageUsers\ViewHelpers;

use App\Models\Account;
use App\Helpers\DateHelper;

class UserIndexViewHelper
{
    /**
     * Get all the users in the account.
     *
     * @param  Account  $account
     * @return array
     */
    public static function data(Account $account): array
    {
        $users = $account->users;

        $userCollection = collect();
        foreach ($users as $user) {
            $userCollection->push([
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'is_account_administrator' => $user->is_account_administrator,
                'invitation_code' => $user->invitation_code,
                'invitation_accepted_at' => DateHelper::formatDate($user->invitation_accepted_at),
                'url' => [
                    'show' => route('settings.user.show', [
                        'user' => $user,
                    ]),
                ],
            ]);
        }

        return [
            'users' => $userCollection,
            'url' => [
                'vault' => [
                    'new' => route('vault.new'),
                ],
            ],
        ];
    }
}
