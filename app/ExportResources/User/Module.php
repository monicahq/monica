<?php

namespace App\ExportResources\User;

use App\ExportResources\ExportResource;

class Module extends ExportResource
{
    protected $columns = [
        'key',
        'translation_key',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'active',
        'delible',
    ];
}
