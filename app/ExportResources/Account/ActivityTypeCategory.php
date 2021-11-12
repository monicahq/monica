<?php

namespace App\ExportResources\Account;

use App\Services\Account\Settings\ExportResource;

class ActivityTypeCategory extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
        'translation_key',
    ];
}
