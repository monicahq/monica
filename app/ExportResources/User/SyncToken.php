<?php

namespace App\ExportResources\User;

use App\ExportResources\ExportResource;

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
