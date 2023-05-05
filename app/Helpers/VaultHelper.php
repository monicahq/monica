<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Facades\Cache;

class VaultHelper
{
    /**
     * Get the friendly name of a vault permission.
     */
    public static function getPermissionFriendlyName(int $permission): string
    {
        switch ($permission) {
            case Vault::PERMISSION_MANAGE:
                $friendlyType = 'Manager';
                break;

            case Vault::PERMISSION_EDIT:
                $friendlyType = 'Editor';
                break;

            case Vault::PERMISSION_VIEW:
                $friendlyType = 'Viewer';
                break;

            default:
                $friendlyType = '';
                break;
        }

        return $friendlyType;
    }

    /**
     * Get the permission for the given user in the given vault.
     */
    public static function getPermission(User $user, Vault $vault): ?int
    {
        return Cache::store('array')
            ->remember("Permission:{$user->id}:{$vault->id}", 5, fn () => self::internalGetPermission($user, $vault));
    }

    private static function internalGetPermission(User $user, Vault $vault): ?int
    {
        $entry = $user->vaults()
            ->wherePivot('vault_id', $vault->id)
            ->first();

        return $entry !== null ? $entry->pivot->permission : null;
    }
}
