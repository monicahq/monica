<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class Task extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'title',
        'description',
        'completed',
        'completed_at',
    ];
}
