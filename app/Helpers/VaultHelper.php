<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VaultHelper
{
    /**
     * Get the friendly name of a vault permission.
     *
     * @param  int  $permission
     * @return string
     */
    public static function getPermissionFriendlyName(int $permission): string
    {
        switch ($permission) {
            case Vault::PERMISSION_MANAGE:
                $friendlyType = trans('account.vault_permission_manage');
                break;

            case Vault::PERMISSION_EDIT:
                $friendlyType = trans('account.vault_permission_edit');
                break;

            case Vault::PERMISSION_VIEW:
                $friendlyType = trans('account.vault_permission_view');
                break;

            default:
                $friendlyType = '';
                break;
        }

        return $friendlyType;
    }

    /**
     * Get the permission for the given user in the given vault.
     *
     * @param  User  $user
     * @param  Vault  $vault
     * @return int|null
     */
    public static function getPermission(User $user, Vault $vault): ?int
    {
        $permission = Cache::store('array')->remember("Permission:{$user->id}:{$vault->id}", 5,
            fn () => DB::table('user_vault')
                ->where([
                    'vault_id' => $vault->id,
                    'user_id' => $user->id,
                ])
                ->select('permission')
                ->first()
        );

        if (! $permission) {
            return null;
        }

        return $permission->permission;
    }
}
