<?php

namespace App\Domains\Contact\ManagePhotos\Web\ViewHelpers;

use App\Helpers\FileHelper;
use App\Models\Contact;
use App\Models\File;

class ContactPhotosShowViewHelper
{
    public static function data(File $file, Contact $contact): array
    {
        return [
            'contact' => [
                'name' => $contact->name,
            ],
            'id' => $file->id,
            'name' => $file->name,
            'mime_type' => $file->mime_type,
            'size' => FileHelper::formatFileSize($file->size),
            'url' => [
                'display' => 'https://ucarecdn.com/'.$file->uuid.'/-/resize/1700x/-/format/auto/-/quality/smart_retina/',
                'download' => $file->cdn_url,
                'show' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'index' => route('contact.photo.index', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
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
