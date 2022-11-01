<?php

namespace App\Domains\Settings\ManageStorage\Web\ViewHelpers;

use App\Helpers\FileHelper;
use App\Models\Account;
use App\Models\Contact;
use App\Models\File;

class StorageIndexViewHelper
{
    public static function data(Account $account): array
    {
        $vaultIds = $account->vaults()->select('id')->get()->toArray();
        $contactIds = Contact::whereIn('vault_id', $vaultIds)->select('id')->get()->toArray();

        $totalSizeDocumentInBytes = File::whereIn('contact_id', $contactIds)
            ->where('type', File::TYPE_DOCUMENT)
            ->sum('size');

        $totalSizeAvatarInBytes = File::whereIn('contact_id', $contactIds)
            ->where('type', File::TYPE_AVATAR)
            ->sum('size');

        $totalSizePhotosInBytes = File::whereIn('contact_id', $contactIds)
            ->where('type', File::TYPE_PHOTO)
            ->sum('size');

        $totalSizeInBytes = $totalSizeDocumentInBytes + $totalSizeAvatarInBytes + $totalSizePhotosInBytes;

        $totalNumberOfPhotos = File::whereIn('contact_id', $contactIds)
            ->where('type', File::TYPE_PHOTO)
            ->count();

        $totalNumberOfDocuments = File::whereIn('contact_id', $contactIds)
            ->where('type', File::TYPE_DOCUMENT)
            ->count();

        $totalNumberOfAvatars = File::whereIn('contact_id', $contactIds)
            ->where('type', File::TYPE_AVATAR)
            ->count();

        $accountLimit = $account->storage_limit_in_mb * 1024 * 1024;

        return [
            'statistics' => [
                'total' => FileHelper::formatFileSize($totalSizeInBytes),
                'total_percent' => $accountLimit > 0 ? round($totalSizeInBytes * 100 / $accountLimit, 0) : 0,
                'photo' => [
                    'total' => $totalNumberOfPhotos,
                    'total_percent' => $totalSizeInBytes > 0 ? round($totalSizePhotosInBytes * 100 / $totalSizeInBytes, 0) : 0,
                    'size' => FileHelper::formatFileSize($totalSizePhotosInBytes),
                ],
                'document' => [
                    'total' => $totalNumberOfDocuments,
                    'total_percent' => $totalSizeDocumentInBytes > 0 ? round($totalSizeDocumentInBytes * 100 / $totalSizeInBytes, 0) : 0,
                    'size' => FileHelper::formatFileSize($totalSizeDocumentInBytes),
                ],
                'avatar' => [
                    'total' => $totalNumberOfAvatars,
                    'total_percent' => $totalSizeAvatarInBytes > 0 ? round($totalSizeAvatarInBytes * 100 / $totalSizeInBytes, 0) : 0,
                    'size' => FileHelper::formatFileSize($totalSizeAvatarInBytes),
                ],
            ],
            'account_limit' => FileHelper::formatFileSize($accountLimit),
            'url' => [
                'settings' => [
                    'index' => route('settings.index'),
                ],
            ],
        ];
    }
}
