<?php

namespace App\ExportResources\Instance;

use App\ExportResources\ExportResource;

class AuditLog extends ExportResource
{
    protected $columns = [
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'author_name',
        'action',
        'objects',
        'audited_at',
        'should_appear_on_dashboard',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                'author' => $this->author->uuid,
                'contact' => $this->contact->uuid,
            ],
        ];
    }
}
