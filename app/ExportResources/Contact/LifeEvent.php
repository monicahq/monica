<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class LifeEvent extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
        'note',
        'happened_at',
        'specific_information',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->lifeEventType != null, function () {
                    return ['type' => $this->lifeEventType->uuid];
                }),
            ],
        ];
    }
}
