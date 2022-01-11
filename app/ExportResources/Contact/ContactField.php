<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class ContactField extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'data',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->contactFieldType !== null, function () {
                    return ['type' => $this->contactFieldType->uuid];
                }),
            ],
        ];
    }
}
