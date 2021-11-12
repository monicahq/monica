<?php

namespace App\ExportResources\User;

use App\Services\Account\Settings\ExportResource;

class SyncToken extends ExportResource
{
    protected $columns = [
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
        'timestamp',
    ];
}
