<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class Gender extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
        'type',
    ];
}
