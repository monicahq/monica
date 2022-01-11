<?php

namespace App\ExportResources\Account;

use App\ExportResources\ExportResource;

class LifeEventCategory extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'core_monica_data',
    ];

    public function data(): ?array
    {
        return [
            'properties' => [
                'translation_key' => $this->default_life_event_category_key,
            ],
        ];
    }
}
