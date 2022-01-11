<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class Call extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'called_at',
        'content',
        'contact_called',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->emotions->count() > 0, [
                    'emotions' => $this->emotions->map(function ($emotion) {
                        return $emotion->name;
                    })->toArray(),
                ]),
            ],
        ];
    }
}
