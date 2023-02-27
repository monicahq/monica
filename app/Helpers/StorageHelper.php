<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\File;

class StorageHelper
{
    /**
     * Indicate if the account can accept another file, depending on the account's
     * limits.
     */
    public static function canUploadFile(Account $account): bool
    {
        // get the file size of all the files in the account
        // the size will be in bytes
        $vaultIds = $account->vaults()->select('id')->get()->toArray();

        $totalSizeInBytes = File::whereIn('vault_id', $vaultIds)->sum('size');

        $accountLimit = $account->storage_limit_in_mb * 1024 * 1024;

        return $totalSizeInBytes < $accountLimit;
    }
}
