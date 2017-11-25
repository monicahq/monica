<?php

namespace App\Http\Resources\Journal;

use Illuminate\Http\Resources\Json\Resource;

class Entry extends Resource
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
            'object' => 'entry',
            'title' => $this->title,
            'post' => $this->post,
            'account' => [
                'id' => $this->account_id,
            ],
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
