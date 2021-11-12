<?php

namespace App\ExportResources\Contact;

use App\Services\Account\Settings\ExportResource;

class Pet extends ExportResource
{
    protected $columns = [
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
                $this->mergeWhen($this->petCategory !== null, [
                    'category' => $this->petCategory->name,
                ]),
            ],
        ];
    }
}
