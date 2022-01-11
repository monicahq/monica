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
                'author' => $this->when($this->author !== null, function () {
                    return $this->author->uuid;
                }),
                'contact' => $this->when($this->contact !== null, function () {
                    return $this->contact->uuid;
                }),
            ],
        ];
    }
}
