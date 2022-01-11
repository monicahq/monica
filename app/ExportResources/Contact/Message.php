<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class Message extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'content',
        'written_at',
        'written_by_me',
    ];
}
