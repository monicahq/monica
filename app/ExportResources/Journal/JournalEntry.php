<?php

namespace App\ExportResources\Journal;

use App\ExportResources\ExportResource;

class JournalEntry extends ExportResource
{
    protected $columns = [
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'date',
    ];

    public function data(): ?array
    {
        $data = $this->getObjectData();
        if ($data !== null) {
            switch ($data['type']) {
                case 'entry':
                    return [
                        'uuid' => $this->journalable->uuid,
                        'properties' => [
                            'type' => $data['type'],
                            'title' => $data['title'],
                            'post' => $data['post'],
                            'date' => $data['date'],
                        ],
                    ];
                case 'day':
                    return [
                        'uuid' => $this->journalable->uuid,
                        'properties' => [
                            'type' => $data['type'],
                            'rate' => $data['rate'],
                            'comment' => $data['comment'],
                            'day' => $data['day'],
                            'month' => $data['month'],
                            'year' => $data['year'],
                        ],
                    ];
            }
        }

        return null;
    }
}
