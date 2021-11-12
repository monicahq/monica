<?php

namespace App\ExportResources\Instance\Emotion;

use App\Services\Account\Settings\ExportResource;

class Emotion extends ExportResource
{
    protected $columns = [
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
                'primary' => $this->primary->name,
                'secondary' => $this->secondary->name,
            ],
        ];
    }
}
