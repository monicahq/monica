<?php

namespace App\Domains\Contact\ManagePhotos\Web\ViewHelpers;

use App\Helpers\FileHelper;
use App\Helpers\StorageHelper;
use App\Models\Contact;
use App\Models\File;

class ContactPhotosIndexViewHelper
{
    public static function data($files, Contact $contact): array
    {
        $photosCollection = $files->map(function (File $file) use ($contact) {
            return self::dto($file, $contact);
        });

        return [
            'contact' => [
                'name' => $contact->name,
            ],
            'photos' => $photosCollection,
            'uploadcare' => StorageHelper::uploadcare(),
            'canUploadFile' => StorageHelper::canUploadFile($contact->vault->account),
            'url' => [
                'show' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'store' => route('contact.photo.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dto(File $file, Contact $contact): array
    {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'mime_type' => $file->mime_type,
            'size' => FileHelper::formatFileSize($file->size),
            'url' => [
                'display' => 'https://ucarecdn.com/'.$file->uuid.'/-/scale_crop/400x400/smart/-/format/auto/-/quality/smart_retina/',
                'download' => $file->cdn_url,
                'show' => route('contact.photo.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'photo' => $file->id,
                ]),
                'destroy' => route('contact.photo.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'photo' => $file->id,
                ]),
            ],
        ];
    }
}
