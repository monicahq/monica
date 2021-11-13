<?php

namespace App\ExportResources\Account;

use App\ExportResources\ExportResource;

class Photo extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'original_filename',
        'filesize',
        'mime_type',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                'dataUrl' => $this->dataUrl(),
            ],
        ];
    }
}
