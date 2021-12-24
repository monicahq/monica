<?php

namespace App\ExportResources\Account;

use App\ExportResources\ExportResource;

class ActivityType extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'translation_key',
        'name',
        'location_type',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->category !== null, function () {
                    return [
                        'category' => $this->category->uuid,
                    ];
                }),
            ],
        ];
    }
}
