<?php

namespace App\Http\Controllers\Vault\Settings\ViewHelpers;

use App\Models\User;
use App\Models\Vault;
use App\Helpers\VaultHelper;

// TODO
class VaultSettingsIndexViewHelper
{
    public static function data(Vault $vault): array
    {
        $templates = $vault->account->templates()->orderBy('name', 'asc')->get();
        $templatesCollection = $templates->map(function ($template) use ($vault) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'is_default' => $vault->default_template_id === $template->id,
            ];
        });

        $usersInAccount = $vault->account->users()->whereNotNull('email_verified_at')->get();
        $usersInVault = $vault->users()->get();
        $usersInAccount = $usersInAccount->diff($usersInVault);
        $usersInAccountCollection = $usersInAccount->map(function ($user) use ($vault) {
            return self::dtoUser($user, $vault);
        });
        $usersInVaultCollection = $usersInVault->map(function ($user) use ($vault) {
            return self::dtoUser($user, $vault);
        });

        return [
            'templates' => $templatesCollection,
            'users_in_vault' => $usersInVaultCollection,
            'users_in_account' => $usersInAccountCollection,
            'url' => [
                'template_update' => route('vault.settings.template.update', [
                    'vault' => $vault->id,
                ]),
                'user_store' => route('vault.settings.user.store', [
                    'vault' => $vault->id,
                ]),
                'update' => route('vault.settings.update', [
                    'vault' => $vault->id,
                ]),
                'destroy' => route('vault.settings.destroy', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }

    public static function dtoUser(User $user, Vault $vault): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'permission' => VaultHelper::getPermission($user, $vault),
            'url' => [
                'update' => route('vault.settings.user.update', [
                    'vault' => $vault->id,
                    'user' => $user->id,
                ]),
                'destroy' => route('vault.settings.user.destroy', [
                    'vault' => $vault->id,
                    'user' => $user->id,
                ]),
            ],
        ];
    }
}
