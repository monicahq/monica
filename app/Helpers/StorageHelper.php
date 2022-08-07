<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\Contact;
use App\Models\File;

class StorageHelper
{
    /**
     * Indicate if the account can accept another file, depending on the account's
     * limits.
     *
     * @param  Account  $account
     * @return bool
     */
    public static function canUploadFile(Account $account): bool
    {
        // get the file size of all the files in the account
        // the size will be in bytes
        $vaultIds = $account->vaults()->select('id')->get()->toArray();
        $contactIds = Contact::whereIn('vault_id', $vaultIds)->select('id')->get()->toArray();

        $totalSizeInBytes = File::whereIn('contact_id', $contactIds)->sum('size');

        $accountLimit = $account->storage_limit_in_mb * 1024 * 1024;

        return $totalSizeInBytes < $accountLimit;
    }
}
