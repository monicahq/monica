<?php

namespace App\Domains\Contact\ManageDocuments\Web\ViewHelpers;

use App\Helpers\FileHelper;
use App\Helpers\StorageHelper;
use App\Models\Contact;
use App\Models\File;

class ModuleDocumentsViewHelper
{
    public static function data(Contact $contact): array
    {
        $documentsCollection = $contact->files()
            ->where('type', File::TYPE_DOCUMENT)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (File $file) use ($contact) {
                return self::dto($file, $contact);
            });

        return [
            'documents' => $documentsCollection,
            'uploadcare' => StorageHelper::uploadcare(),
            'canUploadFile' => StorageHelper::canUploadFile($contact->vault->account),
            'url' => [
                'store' => route('contact.document.store', [
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
                'download' => $file->cdn_url,
                'destroy' => route('contact.document.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'document' => $file->id,
                ]),
            ],
        ];
    }
}
