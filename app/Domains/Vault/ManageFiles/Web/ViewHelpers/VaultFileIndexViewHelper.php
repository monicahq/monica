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
            $contact = $file->contact;

            $filesCollection->push([
                'id' => $file->id,
                'name' => $file->name,
                'mime_type' => $file->mime_type,
                'size' => FileHelper::formatFileSize($file->size),
                'created_at' => DateHelper::format($file->created_at, $user),
                'contact' => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'avatar' => $contact->avatar,
                    'url' => [
                        'show' => route('contact.show', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                        ]),
                    ],
                ],
                'url' => [
                    'download' => $file->cdn_url,
                    'destroy' => route('contact.document.destroy', [
                        'vault' => $contact->vault_id,
                        'contact' => $contact->id,
                        'document' => $file->id,
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
        $contactIds = Contact::where('vault_id', $vault->id)->select('id')->get()->toArray();

        $totalNumberOfPhotos = File::whereIn('contact_id', $contactIds)
            ->where('type', File::TYPE_PHOTO)
            ->count();

        $totalNumberOfDocuments = File::whereIn('contact_id', $contactIds)
            ->where('type', File::TYPE_DOCUMENT)
            ->count();

        $totalNumberOfAvatars = File::whereIn('contact_id', $contactIds)
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
}
