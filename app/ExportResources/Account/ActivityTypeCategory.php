<?php

namespace App\ExportResources\Account;

use App\ExportResources\ExportResource;

class ActivityTypeCategory extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'translation_key',
        'name',
    ];
}
