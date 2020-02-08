<?php

namespace App\Helpers;

use App\Models\Account\Account;
use App\Models\Account\Photo;
use App\Models\Contact\Document;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StorageHelper
{
    /**
     * Get the storage size of the account, in bytes.
     *
     * @param Account $account
     * @return int
     */
    public static function getAccountStorageSize(Account $account)
    {
        $documentsSize = DB::table('documents')
            ->where('account_id', $account->id)
            ->sum('filesize');
        $photosSize = DB::table('photos')
            ->where('account_id', $account->id)
            ->sum('filesize');

        return $documentsSize + $photosSize;
    }

    /**
     * Indicates whether the account has the reached the maximum storage size
     *
     * @param Account $account
     * @return bool
     */
    public static function hasReachedAccountStorageLimit(Account $account)
    {
        if (!config('monica.requires_subscription')) {
            return false;
        }

        $currentAccountSize = self::getAccountStorageSize($account);

        return $currentAccountSize > (config('monica.max_storage_size') * 1000000);
    }
}
