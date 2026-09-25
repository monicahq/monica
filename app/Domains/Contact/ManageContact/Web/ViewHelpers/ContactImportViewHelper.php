<?php

namespace App\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Models\Vault;

class ContactImportViewHelper
{
    public static function data(Vault $vault): array
    {
        return [
            'url' => [
                'import' => route('contact.import.store', [
                    'vault' => $vault->id,
                ]),
                'contact' => [
                    'index' => route('contact.index', [
                        'vault' => $vault->id,
                    ]),
                ],
                'cancel' => route('contact.import.cancel', [
                    'vault' => $vault->id,
                    'importJob' => '__IMPORT_JOB_ID__',
                ]),
            ],
            'max_file_size' => min(
                (int) ini_get('upload_max_filesize') * 1024 * 1024,
                (int) ini_get('post_max_size') * 1024 * 1024,
                50 * 1024 * 1024
            ),
        ];
    }
}
