<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\File;
use Uploadcare\Security\Signature;

class StorageHelper
{
    /**
     * Indicate if the account can accept another file, depending on the account's
     * limits.
     */
    public static function canUploadFile(Account $account): bool
    {
        // if storage limit is 0 then unlimited storage is allowed
        // therefore the function should always return true
        if ($account->storage_limit_in_mb == 0) {
            return true;
        }
        // get the file size of all the files in the account
        // the size will be in bytes
        $vaultIds = $account->vaults()->select('id')->get()->toArray();

        $totalSizeInBytes = File::whereIn('vault_id', $vaultIds)->sum('size');

        $accountLimit = $account->storage_limit_in_mb * 1024 * 1024;

        return $totalSizeInBytes < $accountLimit;
    }

    /**
     * Get the Uploadcare data needed for the views.
     */
    public static function uploadcare(): array
    {
        $signature = config('services.uploadcare.private_key') != '' ? new Signature(config('services.uploadcare.private_key')) : null;

        return [
            'publicKey' => config('services.uploadcare.public_key'),
            'signature' => optional($signature)->getSignature(),
            'expire' => optional(optional($signature)->getExpire())->getTimestamp(),
        ];
    }
}
