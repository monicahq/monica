<?php

namespace App\Http\Resources\Emotion;

use Illuminate\Http\Resources\Json\Resource;

class Emotion extends Resource
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
            'object' => 'emotion',
            'name' => $this->name,
        ];
    }
}
