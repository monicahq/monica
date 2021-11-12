<?php

namespace App\ExportResources\Account;

use App\Services\Account\Settings\ExportResource;

class ActivityType extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
        'translation_key',
        'location_type',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->category !== null, function() {
                    return [
                        'category' => $this->category->uuid
                    ];
                }),
            ]
        ];
    }
}
