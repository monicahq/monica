<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class Reminder extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'initial_date',
        'title',
        'description',
        'frequency_type',
        'frequency_number',
        'delible',
        'inactive',
    ];
}
