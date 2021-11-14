<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class Pet extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->petCategory !== null, function () {
                    return ['category' => $this->petCategory->name];
                }),
            ],
        ];
    }
}
