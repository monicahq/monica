<?php

namespace App\Helpers;

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

class StorageHelper
{
    /**
     * Get a filesystem instance.
     *
     * @param string $name
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    public static function disk($name = null): FilesystemAdapter
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter */
        $disk = Storage::disk($name);

        return $disk;
    }

    /**
     * Get the storage size of the account, in bytes.
     *
     * @param Account $account
     * @return int
     */
    public static function getAccountStorageSize(Account $account): int
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
     * Indicates whether the account has the reached the maximum storage size.
     *
     * @param Account $account
     * @return bool
     */
    public static function hasReachedAccountStorageLimit(Account $account): bool
    {
        if (! config('monica.requires_subscription')) {
            return false;
        }

        $currentAccountSize = self::getAccountStorageSize($account);

        return $currentAccountSize > (config('monica.max_storage_size') * 1000000);
    }
}
