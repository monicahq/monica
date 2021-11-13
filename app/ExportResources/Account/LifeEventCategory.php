<?php

namespace App\ExportResources\Account;

use App\ExportResources\ExportResource;

class LifeEventCategory extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
        'default_life_event_category_key',
        'core_monica_data',
    ];
}
