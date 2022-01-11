<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class Debt extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'amount',
        'currency',
        'status',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                'in_debt' => $this->in_debt === 'yes',
            ],
        ];
    }
}
