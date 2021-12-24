<?php

namespace App\ExportResources\Account;

use App\ExportResources\ExportResource;

class LifeEventType extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
        'core_monica_data',
        'specific_information_structure',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                'translation_key' => $this->default_life_event_type_key,
                $this->mergeWhen($this->lifeEventCategory !== null, function () {
                    return [
                        'category' => $this->lifeEventCategory->uuid,
                    ];
                }),
            ],
        ];
    }
}
