<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class Document extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'original_filename',
        'filesize',
        'type',
        'mime_type',
        'number_of_downloads',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen(($dataUrl = $this->dataUrl()) !== null, [
                    'dataUrl' => $dataUrl,
                ]),
            ],
        ];
    }
}
