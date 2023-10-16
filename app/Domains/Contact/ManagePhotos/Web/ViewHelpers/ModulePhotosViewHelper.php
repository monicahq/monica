<?php

namespace App\Domains\Contact\ManagePhotos\Web\ViewHelpers;

use App\Helpers\FileHelper;
use App\Helpers\StorageHelper;
use App\Models\Contact;
use App\Models\File;

class ModulePhotosViewHelper
{
    public static function data(Contact $contact): array
    {
        $photosCollection = $contact->files()
            ->where('type', File::TYPE_PHOTO)
            ->take(6)
            ->get()
            ->map(fn (File $file) => self::dto($file, $contact));

        return [
            'photos' => $photosCollection,
            'uploadcare' => StorageHelper::uploadcare(),
            'canUploadFile' => StorageHelper::canUploadFile($contact->vault->account),
            'url' => [
                'index' => route('contact.photo.index', [
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
                'display' => 'https://ucarecdn.com/'.$file->uuid.'/-/scale_crop/300x300/smart/-/format/auto/-/quality/smart_retina/',
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
