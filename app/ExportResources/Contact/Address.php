<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class Address extends ExportResource
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
                'street' => $this->place->street,
                'city' => $this->place->city,
                'province' => $this->place->province,
                'postal_code' => $this->place->postal_code,
                'latitude' => $this->place->latitude,
                'longitude' => $this->place->longitude,
                'country' => $this->place->country,
            ],
        ];
    }
}
