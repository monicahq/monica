<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class Conversation extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'happened_at',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->contactFieldType !== null, function () {
                    return ['contact_field_type' => $this->contactFieldType->uuid];
                }),
                'messages' => Message::collection($this->messages),
            ],
        ];
    }
}
