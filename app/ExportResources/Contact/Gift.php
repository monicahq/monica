<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class Gift extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
        'comment',
        'url',
        'amount',
        'status',
        'date',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->recipient !== null, function () {
                    return ['recipient' => $this->recipient->uuid];
                }),
                $this->mergeWhen($this->photos->count() > 0, [
                    'photos' => $this->photos->mapUuid(),
                ]),
            ],
        ];
    }
}
