<?php

namespace App\Http\Resources\Tag;

use Illuminate\Http\Resources\Json\Resource;

class Tag extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'tag',
            'name' => $this->name,
            'name_slug' => $this->name_slug,
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
