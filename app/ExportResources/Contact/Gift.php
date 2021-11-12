<?php

namespace App\ExportResources\Contact;

use App\Services\Account\Settings\ExportResource;

class Gift extends ExportResource
{
    protected $columns = [
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
        'comment',
        'url',
        'amount',
        'status',
        'date'
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->recipient !== null, function () {
                    return [
                        'recipient' => $this->recipient->uuid,
                    ];
                }),
            ],
        ];
    }
}
