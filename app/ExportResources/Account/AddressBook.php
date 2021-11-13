<?php

namespace App\ExportResources\Account;

use App\ExportResources\ExportResource;

class AddressBook extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'description',
        'name',
    ];

    public function data(): ?array
    {
        return  [
            'user' => $this->user->uuid,
            'contacts' => $this->contacts->mapUuid(),
        ];
    }
}
