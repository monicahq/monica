<?php

namespace App\Domains\Vault\ManageFiles\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Helpers\FileHelper;
use App\Models\Contact;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;

class VaultFileIndexViewHelper
{
    public static function data($files, User $user, Vault $vault): array
    {
        $filesCollection = collect();
        foreach ($files as $file) {
            $filesCollection->push([
                'id' => $file->id,
                'name' => $file->name,
                'mime_type' => $file->mime_type,
                'size' => FileHelper::formatFileSize($file->size),
                'created_at' => DateHelper::format($file->created_at, $user),
                'object' => self::getObjectDetails($file),
                'url' => [
                    'download' => $file->cdn_url,
                    'destroy' => route('vault.files.destroy', [
                        'vault' => $file->vault_id,
                        'file' => $file->id,
                    ]),
                ],
            ]);
        }

        return [
            'files' => $filesCollection,
            'statistics' => self::statistics($vault),
        ];
    }

    public static function statistics(Vault $vault): array
    {
        $totalNumberOfPhotos = File::where('vault_id', $vault->id)
            ->where('type', File::TYPE_PHOTO)
            ->count();

        $totalNumberOfDocuments = File::where('vault_id', $vault->id)
            ->where('type', File::TYPE_DOCUMENT)
            ->count();

        $totalNumberOfAvatars = File::where('vault_id', $vault->id)
            ->where('type', File::TYPE_AVATAR)
            ->count();

        return [
            'statistics' => [
                'all' => $totalNumberOfPhotos + $totalNumberOfDocuments + $totalNumberOfAvatars,
                'photos' => $totalNumberOfPhotos,
                'documents' => $totalNumberOfDocuments,
                'avatars' => $totalNumberOfAvatars,
            ],
            'url' => [
                'index' => route('vault.files.index', [
                    'vault' => $vault->id,
                ]),
                'photos' => route('vault.files.photos', [
                    'vault' => $vault->id,
                ]),
                'documents' => route('vault.files.documents', [
                    'vault' => $vault->id,
                ]),
                'avatars' => route('vault.files.avatars', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }

    public static function getObjectDetails(File $file): array
    {
        return match ($file->fileable_type) {
            Contact::class => [
                'type' => 'contact',
                'id' => $file->ufileable->id,
                'name' => $file->ufileable->name,
                'avatar' => $file->ufileable->avatar,
                'url' => [
                    'show' => route('contact.show', [
                        'vault' => $file->ufileable->vault_id,
                        'contact' => $file->ufileable->id,
                    ]),
                ],
            ],
            default => [],
        };
    }
}
