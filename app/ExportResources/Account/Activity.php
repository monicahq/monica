<?php

namespace App\ExportResources\Account;

use App\ExportResources\ExportResource;

class Activity extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'summary',
        'description',
        'happened_at',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->type !== null, function () {
                    return [
                        'type' => $this->type->uuid,
                    ];
                }),
            ],
        ];
    }
}
