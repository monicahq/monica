<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class ContactFieldType extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'name',
        'fontawesome_icon',
        'protocol',
        'delible',
        'type',
    ];
}
