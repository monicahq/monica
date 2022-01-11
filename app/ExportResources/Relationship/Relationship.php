<?php

namespace App\ExportResources\Relationship;

use App\ExportResources\ExportResource;

class Relationship extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                'type' => $this->relationshipType->name,
                'contact_is' => $this->contactIs->uuid,
                'of_contact' => $this->ofContact->uuid,
            ],
        ];
    }
}
