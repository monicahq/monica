<?php

namespace App\ExportResources\Account;

use App\Services\Account\Settings\ExportResource;

class LifeEventType extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
        'default_life_event_type_key',
        'core_monica_data',
        'specific_information_structure',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->lifeEventCategory !== null, function () {
                    return [
                        'category' => $this->lifeEventCategory->uuid,
                    ];
                }),
            ],
        ];
    }
}
