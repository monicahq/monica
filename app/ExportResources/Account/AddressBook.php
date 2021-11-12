<?php

namespace App\ExportResources\Account;

use App\Services\Account\Settings\ExportResource;

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
            'contacts' => $this->contacts->map(function ($contact) {
                return $contact->uuid;
            })->toArray(),
        ];
    }
}
