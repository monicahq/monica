<?php

namespace App\Http\Resources\Settings\Compliance;

use Illuminate\Http\Resources\Json\Resource;

class Compliance extends Resource
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
            'object' => 'term',
            'term_version' => $this->term_version,
            'term_content' => $this->term_content,
            'privacy_version' => $this->privacy_version,
            'privacy_content' => $this->privacy_content,
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
