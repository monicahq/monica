<?php

namespace App\Helpers;

use App\Models\Vault;

class VaultHelper
{
    /**
     * Get the friendly name of a vault permission.
     *
     * @param int $permission
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
        }

        return $friendlyType;
    }
}
